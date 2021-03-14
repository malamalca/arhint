<?php
declare(strict_types=1);

namespace LilCrm\Command;

if (!defined('STDIN')) {
    define('STDIN', fopen('php://stdin', 'r'));
}

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\ORM\TableRegistry;

/**
 * Various health routines.
 */
class LilCrmCommand extends Command
{
    /**
     * Start the Command and interactive console.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return int|void|null The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        switch ($args->getArgumentAt(0)) {
            case 'deleteStaleRecords':
                $ContactsEmails = TableRegistry::get('LilCrm.ContactsEmails');
                $this->deleteStaleRecords($ContactsEmails);

                $ContactsAddresses = TableRegistry::get('LilCrm.ContactsAddresses');
                $this->deleteStaleRecords($ContactsAddresses);

                $ContactsPhones = TableRegistry::get('LilCrm.ContactsPhones');
                $this->deleteStaleRecords($ContactsPhones);

                $ContactsAccounts = TableRegistry::get('LilCrm.ContactsAccounts');
                $this->deleteStaleRecords($ContactsAccounts);

                break;
            case 'findDuplicates':
                $this->findDuplicates($io);
                break;
            default:
                $io->out('Available subcommands: deleteStaleRecords, findDuplicates');
        }
    }

    /**
     * Searches and resolves contact duplicates.
     *
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return void
     */
    public function findDuplicates(ConsoleIo $io)
    {
        $ContactsTable = TableRegistry::get('LilCrm.Contacts');
        $ContactsAccountsTable = TableRegistry::get('LilCrm.ContactsAccounts');
        $ContactsAddressesTable = TableRegistry::get('LilCrm.ContactsAddresses');
        $ContactsPhonesTable = TableRegistry::get('LilCrm.ContactsPhones');
        $ContactsEmailsTable = TableRegistry::get('LilCrm.ContactsEmails');

        $InvoicesClients = TableRegistry::get('LilInvoices.InvoicesClients');

        $q = $ContactsTable->find();

        $contacts = $q->select([
            'id', 'owner_id', 'tax_no',
            'count' => $q->func()->count('*'),
        ])
        ->where(['tax_no IS NOT' => null])
        ->group(['owner_id', 'tax_no'])
        ->having(['count >' => 1])
        ->all();

        foreach ($contacts as $c) {
            $sameContacts = $ContactsTable->find()
                ->select()
                ->where(['owner_id' => $c->owner_id, 'tax_no' => $c->tax_no])
                ->contain(['ContactsAccounts', 'ContactsAddresses', 'ContactsEmails', 'ContactsPhones'])
                ->all();

            $io->out(sprintf('Merging same contacts with Tax-No "%s": ', $c->tax_no));

            $inputOptions = ['S'];
            foreach ($sameContacts as $i => $contact) {
                $io->out($i . '. ' . $contact->title);
                $inputOptions[] = (string)$i;
            }
            $answer = $io->askChoice('Please enter contact number to keep or "S" to skip:', $inputOptions, 'S');

            if ($answer != 'S') {
                // this is the contact we want to keep
                $keepContact = $sameContacts->take(1, (int)$answer)->first();

                $io->info('KEEP CONTACT DATA:');
                $io->info('Title: ' . $keepContact->title);
                foreach ($keepContact->contacts_accounts as $j => $account) {
                    $io->info(' Account ' . $j . ': ' . $account->iban);
                }
                foreach ($keepContact->contacts_addresses as $j => $address) {
                    $io->info(' Address ' . $j . ': ' . $address->street . ', ' . $address->zip . ' ' . $address->city);
                }
                foreach ($keepContact->contacts_emails as $j => $email) {
                    $io->info(' Email ' . $j . ': ' . $email->email);
                }
                foreach ($keepContact->contacts_phones as $j => $phone) {
                    $io->info(' Phone ' . $j . ': ' . $phone->no);
                }
                $io->out('', 1);

                foreach ($sameContacts as $i => $disposeContact) {
                    if ($i != (int)$answer) {
                        $io->out(sprintf('DELETING "%s"', $disposeContact->title));

                        foreach ($disposeContact->contacts_accounts as $j => $account) {
                            if ($io->askChoice(sprintf('Keep account "%s"?', $account->iban), ['Y', 'N'], 'N') == 'Y') {
                                $account->contact_id = $keepContact->id;
                                $ContactsAccountsTable->save($account);
                            }
                        }

                        foreach ($disposeContact->contacts_addresses as $j => $address) {
                            if (
                                $io->askChoice(sprintf(
                                    'Keep address "%s"?',
                                    $address->street . ', ' . $address->zip . ' ' . $address->city
                                ), ['Y', 'N'], 'N') == 'Y'
                            ) {
                                $address->contact_id = $keepContact->id;
                                $ContactsAddressesTable->save($address);
                            }
                        }

                        foreach ($disposeContact->contacts_emails as $j => $email) {
                            if ($io->askChoice(sprintf('Keep email "%s"?', $email->email), ['Y', 'N'], 'N') == 'Y') {
                                $email->contact_id = $keepContact->id;
                                $ContactsEmailsTable->save($email);
                            }
                        }

                        foreach ($disposeContact->contacts_phones as $j => $phones) {
                            if ($io->askChoice(sprintf('Keep phone "%s"? ', $phones->no), ['Y', 'N'], 'N') == 'Y') {
                                $phones->contact_id = $keepContact->id;
                                $ContactsPhonesTable->save($phones);
                            }
                        }

                        $InvoicesClients->updateAll(
                            ['contact_id' => $keepContact->id],
                            ['contact_id' => $disposeContact->id]
                        );

                        $ContactsTable->updateAll(
                            ['company_id' => $keepContact->id],
                            ['company_id' => $disposeContact->id]
                        );

                        $ContactsTable->delete($disposeContact);
                    }
                }
            }

            $io->out('COMPLETED. Process next contact.', 4);
        }
    }

    /**
     * Delete records that do not have owner
     *
     * @param object $class TableClass
     * @return void
     */
    private function deleteStaleRecords($class)
    {
        $Contacts = TableRegistry::get('LilCrm.Contacts');

        $q = $class->find();

        $staleRecords = $q
            ->select()
            ->all();

        foreach ($staleRecords as $rec) {
            $count = $Contacts->find()->select(['id'])->where(['id' => $rec->contact_id])->count();
            if ($count == 0) {
                var_dump($rec);
                //$class->delete($rec);
            }
        }
    }
}
