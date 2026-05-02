<?php
declare(strict_types=1);

namespace Expenses\Lib;

use Genkgo\Camt\Config;
use Genkgo\Camt\DTO\Balance;
use Genkgo\Camt\DTO\Creditor;
use Genkgo\Camt\DTO\Debtor;
use Genkgo\Camt\DTO\IbanAccount;
use Genkgo\Camt\Exception\ReaderException;
use Genkgo\Camt\Reader;
use RuntimeException;

/**
 * Parses ISO 20022 camt.053 bank statement XML files using genkgo/camt.
 *
 * Returns an array suitable for creating BankStatement and BankStatementEntries entities.
 */
class BankStatementImport
{
    /**
     * Parse a camt.053 XML string.
     *
     * @param string $xmlContent Raw XML content.
     * @return array<string, mixed> Parsed data with 'statement' and 'entries' keys.
     * @throws \RuntimeException When the XML is invalid or missing required elements.
     */
    public function parse(string $xmlContent): array
    {
        $reader = new Reader(Config::getDefault());

        try {
            $message = $reader->readString($xmlContent);
        } catch (ReaderException $e) {
            throw new RuntimeException(__d('expenses', 'Invalid XML file: {0}', $e->getMessage()), 0, $e);
        }

        $records = $message->getRecords();
        if (empty($records)) {
            throw new RuntimeException(__d('expenses', 'No statement found in XML file.'));
        }

        /** @var \Genkgo\Camt\Camt053\DTO\Statement $stmt */
        $stmt = $records[0];

        $stmtId = $stmt->getId();
        $seqNoRaw = $stmt->getLegalSequenceNumber();
        $seqNo = $seqNoRaw !== null && $seqNoRaw !== '' ? (int)$seqNoRaw : null;

        // IBAN
        $account = $stmt->getAccount();
        if (!($account instanceof IbanAccount)) {
            throw new RuntimeException(__d('expenses', 'Missing IBAN in XML file.'));
        }
        $iban = (string)$account->getIban();

        // Date from createdOn
        $datIssue = $stmt->getCreatedOn()->format('Y-m-d');

        // Balances
        $currency = 'EUR';
        $openingBal = 0.0;
        $closingBal = 0.0;

        foreach ($stmt->getBalances() as $balance) {
            $money = $balance->getAmount();
            $currency = $money->getCurrency()->getCode();
            // moneyphp stores amounts in minor units (cents); use abs() as sign is conveyed by CdtDbtInd
            $amount = abs((float)$money->getAmount() / 100);

            if ($balance->getType() === Balance::TYPE_OPENING) {
                $openingBal = $amount;
            } elseif ($balance->getType() === Balance::TYPE_CLOSING) {
                $closingBal = $amount;
            }
        }

        $saldo = $closingBal - $openingBal;

        // Entries
        $entries = [];
        $totalCredit = 0.0;
        $totalDebit = 0.0;
        $countCredit = 0;
        $countDebit = 0;

        foreach ($stmt->getEntries() as $entry) {
            $entryMoney = $entry->getAmount();
            $entryAmount = abs((float)$entryMoney->getAmount() / 100);
            $indicator = $entry->getCreditDebitIndicator() ?? '';

            $credit = '0.00';
            $debit = '0.00';
            if ($indicator === 'CRDT') {
                $credit = number_format($entryAmount, 2, '.', '');
                $totalCredit += $entryAmount;
                $countCredit++;
            } else {
                $debit = number_format($entryAmount, 2, '.', '');
                $totalDebit += $entryAmount;
                $countDebit++;
            }

            $bookingDate = $entry->getBookingDate()?->format('Y-m-d');
            $entryRef = $entry->getAccountServicerReference() ?? '';

            $client = '';
            $counterIban = '';
            $ref = '';
            $descript = '';

            $detail = $entry->getTransactionDetail();
            if ($detail !== null) {
                // Counterparty name and IBAN
                foreach ($detail->getRelatedParties() as $relatedParty) {
                    $partyType = $relatedParty->getRelatedPartyType();
                    $partyAccount = $relatedParty->getAccount();

                    // For CRDT the payer is the Debtor; for DBIT the payee is the Creditor
                    $isCounterparty = ($indicator === 'CRDT' && $partyType instanceof Debtor)
                        || ($indicator === 'DBIT' && $partyType instanceof Creditor);

                    if ($isCounterparty) {
                        $client = (string)($partyType->getName() ?? '');
                        if ($partyAccount instanceof IbanAccount) {
                            $counterIban = (string)$partyAccount->getIban();
                        }
                        break;
                    }
                }

                // Remittance information
                $remittance = $detail->getRemittanceInformation();
                if ($remittance !== null) {
                    // Structured: creditor reference + additional info
                    foreach ($remittance->getStructuredBlocks() as $block) {
                        $cri = $block->getCreditorReferenceInformation();
                        if ($cri !== null && $ref === '') {
                            $ref = (string)($cri->getRef() ?? '');
                        }
                        if ($descript === '') {
                            $descript = (string)($block->getAdditionalRemittanceInformation() ?? '');
                        }
                    }
                    // Unstructured fallback
                    if ($descript === '') {
                        foreach ($remittance->getUnstructuredBlocks() as $unstructured) {
                            $descript = $unstructured->getMessage();
                            break;
                        }
                    }
                }
            }

            $entries[] = [
                'no' => $entryRef,
                'client' => $client,
                'descript' => $descript,
                'credit' => $credit,
                'debit' => $debit,
                'iban' => $counterIban,
                'ref' => $ref,
                'dat_issue' => $bookingDate,
            ];
        }

        return [
            'statement' => [
                'no' => $stmtId,
                'seq_no' => $seqNo,
                'kind' => 'camt.053',
                'iban' => $iban,
                'dat_issue' => $datIssue,
                'currency' => $currency,
                'total_credit' => number_format($totalCredit, 2, '.', ''),
                'total_debit' => number_format($totalDebit, 2, '.', ''),
                'count_credit' => $countCredit,
                'count_debit' => $countDebit,
                'saldo' => number_format($saldo, 2, '.', ''),
                'balance' => number_format($openingBal, 2, '.', ''),
            ],
            'entries' => $entries,
        ];
    }
}
