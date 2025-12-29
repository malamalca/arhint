<?php
declare(strict_types=1);

namespace Crm\Mailer;

use App\Mailer\ArhintMailer;
use App\Model\Entity\User;
use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Crm\Model\Entity\Adrema;
use Crm\Model\Entity\AdremasContact;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * @psalm-suppress UnusedClass
 */
class CrmMailer extends ArhintMailer
{
    /**
     * Send dopis
     *
     * @param \App\Model\Entity\User $sender Sender user entity
     * @param \Crm\Model\Entity\AdremasContact $address Contact entity
     * @param \Crm\Model\Entity\Adrema $adrema Adrema entity
     * @return void
     */
    public function dopis(User $sender, AdremasContact $address, Adrema $adrema): void
    {
        if (empty($address->contacts_email)) {
            throw new InvalidArgumentException('Contact has no email address');
        }
        $this
            ->setFrom($sender->email)
            ->setTo(Configure::read('debug') ? 'miha.nahtigal@arhim.si' : $address->contacts_email->email)
            ->setSubject($adrema->user_data['subject'] ?? __d('crm', 'Adrema: {0}', $adrema->title))
            ->setViewVars(['user' => $sender, 'address' => $address, 'adrema' => $adrema, 'data' => $adrema->user_data])
            ->setEmailFormat('both')
            ->viewBuilder()
                ->setTemplate('Crm.dopis')
                ->addHelper('Html');

        $atts = [];
        foreach ($adrema->attachments as $attachment) {
            $atts[$attachment->filename] = $attachment->getFilePath();
        }

        $this->setAttachments($atts);
    }

    /**
     * Adrema SloMnenja
     *
     * @param \App\Model\Entity\User $sender Sender user entity
     * @param \Crm\Model\Entity\AdremasContact $address Contact entity
     * @param \Crm\Model\Entity\Adrema $adrema Adrema entity
     * @return void
     */
    public function sloMnenja(User $sender, AdremasContact $address, Adrema $adrema): void
    {
        if (empty($address->contacts_email)) {
            throw new InvalidArgumentException('Contact has no email address');
        }
        $this
            ->setFrom($sender->email)
            ->setTo(Configure::read('debug') ? 'miha.nahtigal@arhim.si' : $address->contacts_email->email)
            ->setSubject('Vloga za mnenje za gradnjo')
            ->setViewVars(['user' => $sender, 'address' => $address, 'data' => $adrema->user_data, 'adrema' => $adrema])
            ->setEmailFormat('both')
            ->viewBuilder()
                ->setTemplate('Crm.slo_mnenja')
                ->addHelper('Html');

        // excel attachment
        if (empty($adrema->user_data['xls'])) {
            throw new InvalidArgumentException('Adrema has no xls form specified');
        }
        $attachment = (new Collection($adrema->form_attachments))->firstMatch(['id' => $adrema->user_data['xls']]);
        $spreadsheet = IOFactory::load($attachment->getFilePath());

        //change it
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('C32', $address->contact->title ?? '');
        $sheet->setCellValue('C33', (string)$address->contacts_address);
        $sheet->setCellValue('C34', (string)($address->user_data['opis'] ?? ''));

        $sheet->setCellValue('C38', (string)($address->user_data['stPogojev'] ?? ''));
        $sheet->setCellValue('C39', (string)($address->user_data['datumPogojev'] ?? ''));

        //write it again to Filesystem with the same name (=replace)
        //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Tcpdf');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        $atts = [];
        $atts[$attachment->filename] = ['data' => $excelOutput];

        // other attachemts
        if (!empty($address->attachments)) {
            foreach ((array)$address->attachments as $attachment) {
                $atts[$attachment->filename] = $attachment->getFilePath();
            }
        }
        if (!empty($adrema->attachments)) {
            foreach ($adrema->attachments as $attachment) {
                $atts[$attachment->filename] = $attachment->getFilePath();
            }
        }

        $this->setAttachments($atts);
    }

    /**
     * Adrema SloPogoji
     *
     * @param \App\Model\Entity\User $sender Sender user entity
     * @param \Crm\Model\Entity\AdremasContact $address Contact entity
     * @param \Crm\Model\Entity\Adrema $adrema Adrema entity
     * @return void
     */
    public function sloPogoji(User $sender, AdremasContact $address, Adrema $adrema): void
    {
        if (empty($address->contacts_email)) {
            throw new InvalidArgumentException('Contact has no email address');
        }
        $this
            ->setFrom($sender->email)
            ->setTo(Configure::read('debug') ? 'miha.nahtigal@arhim.si' : $address->contacts_email->email)
            ->setSubject('Vloga za projektne pogoje za gradnjo')
            ->setViewVars(['user' => $sender, 'address' => $address, 'data' => $adrema->user_data, 'adrema' => $adrema])
            ->setEmailFormat('both')
            ->viewBuilder()
                ->setTemplate('Crm.slo_pogoji')
                ->addHelper('Html');

        // excel attachment
        if (empty($adrema->user_data['xls'])) {
            throw new InvalidArgumentException('Adrema has no xls form specified');
        }
        $attachment = (new Collection($adrema->form_attachments))->firstMatch(['id' => $adrema->user_data['xls']]);
        $spreadsheet = IOFactory::load($attachment->getFilePath());

        //change it
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('C32', $address->contact->title ?? '');
        $sheet->setCellValue('C33', (string)$address->contacts_address);

        //write it again to Filesystem with the same name (=replace)
        //$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Tcpdf');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        ob_start();
        $writer->save('php://output');
        $excelOutput = ob_get_clean();

        $atts = [];
        $atts[$attachment->filename] = ['data' => $excelOutput];

        // other attachemts
        if (!empty($address->attachments)) {
            foreach ((array)$address->attachments as $attachment) {
                $atts[$attachment->filename] = $attachment->getFilePath();
            }
        }
        if (!empty($adrema->attachments)) {
            foreach ($adrema->attachments as $attachment) {
                $atts[$attachment->filename] = $attachment->getFilePath();
            }
        }

        $this->setAttachments($atts);
    }

    /**
     * Render content and send email using configured transport.
     *
     * @param string $content Content.
     * @return array
     * @phpstan-return array{headers: string, message: string, ...}
     */
    public function deliver(?string $content = null): array
    {
        $ret = parent::deliver($content);

        if ($ret) {
            $vars = $this->viewBuilder()->getVars();
            /** @var \App\Model\Table\LogsTable $LogsTable */
            $LogsTable = TableRegistry::getTableLocator()->get('App.Logs');
            $LogsTable::log(
                model: 'Adremas',
                foreignId: $this->viewBuilder()->getVar('adrema')->id,
                userId: $this->currentUser->id,
                action: 'AdremaEmail',
                details: json_encode([
                    'kind' => $vars['adrema']->kind,
                    'kind_type' => $vars['adrema']->kind_type,
                    'from' => $vars['user']->id ?? null,
                    'to' => $vars['address']->id ?? null,
                ], JSON_THROW_ON_ERROR),
            );
        }

        return $ret;
    }
}
