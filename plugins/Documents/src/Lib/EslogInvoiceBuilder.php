<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\I18n\Date;
use Cake\ORM\TableRegistry;
use Documents\Model\Entity\Invoice;
use Documents\Model\Entity\Vat;

/**
 * Fills a new invoice entity with data parsed from an eSlog 2.0 document by {@see \Documents\Lib\EslogImport}.
 */
class EslogInvoiceBuilder
{
    /**
     * Apply parsed eSlog import data to a new invoice entity.
     *
     * @param \Documents\Model\Entity\Invoice $document Invoice entity.
     * @param array<string, mixed> $importData Parsed eSlog data.
     * @param string $companyId Company (owner) id.
     * @return \Documents\Model\Entity\Invoice
     */
    public function apply(
        Invoice $document,
        array $importData,
        string $companyId,
    ): Invoice {
        // Apply invoice header fields
        $invoiceData = $importData['invoice'] ?? [];

        if (!empty($invoiceData['no'])) {
            $document->no = $invoiceData['no'];
        }
        if (!empty($invoiceData['title'])) {
            $document->title = $invoiceData['title'];
        }
        if (!empty($invoiceData['dat_issue'])) {
            $parsedDate = Date::parseDate($invoiceData['dat_issue'], 'yyyy-MM-dd');
            if ($parsedDate) {
                $document->dat_issue = $parsedDate;
                // Fall back to the issue date when no explicit service date was parsed
                $document->dat_service = $parsedDate;
            }
        }
        if (!empty($invoiceData['dat_service'])) {
            $serviceDate = Date::parseDate($invoiceData['dat_service'], 'yyyy-MM-dd');
            if ($serviceDate) {
                $document->dat_service = $serviceDate;
            }
        }
        if (!empty($invoiceData['dat_expire'])) {
            $expireDate = Date::parseDate($invoiceData['dat_expire'], 'yyyy-MM-dd');
            if ($expireDate) {
                $document->dat_expire = $expireDate;
            }
        }
        if (!empty($invoiceData['pmt_type'])) {
            $document->pmt_type = $invoiceData['pmt_type'];
        }
        if (!empty($invoiceData['pmt_module'])) {
            $document->pmt_module = $invoiceData['pmt_module'];
        }
        if (!empty($invoiceData['pmt_ref'])) {
            $document->pmt_ref = $invoiceData['pmt_ref'];
        }

        // Apply client data using newEntity to create proper DocumentsClient entities
        /** @var \Documents\Model\Table\DocumentsClientsTable $DocumentsClients */
        $DocumentsClients = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');

        $ownerId = $companyId;

        // Apply issuer data (seller)
        $issuerData = $importData['issuer'] ?? [];
        if (!empty($issuerData)) {
            $issuerData['contact_id'] = $this->_findContactIdByTaxNo($ownerId, $issuerData['tax_no'] ?? null);
            $document->issuer = $DocumentsClients->newEntity(array_merge($issuerData, ['kind' => 'II']));
        }

        // Apply receiver/buyer data. In the eSlog XML the seller (issuer) is the
        // external party and the buyer/invoicee (receiver) is "us"; that mapping
        // holds regardless of the counter direction. The issuer above is filled
        // from the seller, the receiver/buyer below from the invoicee.
        $receiverData = $importData['receiver'] ?? $importData['buyer'] ?? [];
        if (!empty($receiverData)) {
            $receiverData['contact_id'] = $this->_findContactIdByTaxNo($ownerId, $receiverData['tax_no'] ?? null);
            $document->receiver = $DocumentsClients->newEntity(array_merge($receiverData, ['kind' => 'IV']));
        }
        $buyerData = $importData['buyer'] ?? $importData['receiver'] ?? [];
        if (!empty($buyerData)) {
            $buyerData['contact_id'] = $this->_findContactIdByTaxNo($ownerId, $buyerData['tax_no'] ?? null);
            $document->buyer = $DocumentsClients->newEntity(array_merge($buyerData, ['kind' => 'BY']));
        }

        $counterDirection = $document->documents_counter->direction ?? 'issued';

        // Apply line items
        $items = $importData['items'] ?? [];

        /** @var \Documents\Model\Table\VatsTable $VatsTable */
        $VatsTable = TableRegistry::getTableLocator()->get('Documents.Vats');
        $vatLevels = $VatsTable->levels($companyId);

        if (!empty($items)) {
            /** @var \Documents\Model\Table\InvoicesItemsTable $InvoicesItems */
            $InvoicesItems = TableRegistry::getTableLocator()->get('Documents.InvoicesItems');
            /** @var \Documents\Model\Table\InvoicesTaxesTable $InvoicesTaxes */
            $InvoicesTaxes = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxes');

            $invoiceItems = [];
            $taxGroups = [];
            $netTotal = 0;
            $totalWithVat = 0;

            foreach ($items as $itemData) {
                $vatPercent = (float)($itemData['vat_percent'] ?? 0);
                $matchedVat = $this->_findVatByPercent($vatLevels, $vatPercent);

                $qty = (float)($itemData['qty'] ?? 1);
                $price = (float)($itemData['price'] ?? 0);
                $discount = (float)($itemData['discount'] ?? 0);
                $vatId = $matchedVat?->id;
                $vatTitle = $matchedVat ? $matchedVat->descript : '';

                // Marshal as a proper entity so the edit template can read it as an object
                $invoiceItems[] = $InvoicesItems->newEntity([
                    'descript' => $itemData['descript'] ?? '',
                    'qty' => $qty,
                    'unit' => $itemData['unit'] ?? 'pcs',
                    'price' => $price,
                    'discount' => $discount,
                    'vat_id' => $vatId,
                    'vat_title' => $vatTitle,
                    'vat_percent' => $vatPercent,
                ]);

                $itemNet = round($qty * $price, 2);
                if ($discount > 0) {
                    $itemNet = round($itemNet * (1 - $discount / 100), 2);
                }
                $netTotal += $itemNet;
                $totalWithVat += round($itemNet * (1 + $vatPercent / 100), 2);

                // Group net amounts by VAT rate for the tax breakdown
                if (!isset($taxGroups[(string)$vatPercent])) {
                    $taxGroups[(string)$vatPercent] = [
                        'vat_percent' => $vatPercent,
                        'vat_title' => $vatTitle,
                        'vat_id' => $vatId,
                        'base' => 0,
                    ];
                }
                $taxGroups[(string)$vatPercent]['base'] = round(
                    $taxGroups[(string)$vatPercent]['base'] + $itemNet,
                    2,
                );
            }

            $document->invoices_items = $invoiceItems;

            $invoicesTaxes = [];
            foreach ($taxGroups as $group) {
                $invoicesTaxes[] = $InvoicesTaxes->newEntity($group);
            }
            $document->invoices_taxes = $invoicesTaxes;

            $document->net_total = round($netTotal, 2);
            $document->total = round($totalWithVat, 2);
        }

        // For received invoices with taxes section instead of items
        if ($counterDirection === 'received' && empty($items)) {
            $totalData = $importData['invoice'] ?? [];
            if (!empty($totalData['total'])) {
                $document->total = (float)$totalData['total'];
            }
            if (!empty($totalData['net_total'])) {
                $document->net_total = (float)$totalData['net_total'];
            }
        }

        return $document;
    }

    /**
     * Look up an existing CRM contact by tax number, scoped to the current company.
     *
     * Tax numbers are unique per owner (see ContactsTable's uniqueTax rule), so a match
     * lets the imported party be linked to the existing contact instead of only carrying
     * a snapshot of its details.
     *
     * @param string $ownerId Current user's company id.
     * @param mixed $taxNo Tax number parsed from the eSlog data, if any.
     * @return string|null Matching contact id, or null when not found.
     */
    private function _findContactIdByTaxNo(string $ownerId, mixed $taxNo): ?string
    {
        if (empty($taxNo) || !is_string($taxNo) || $ownerId === '') {
            return null;
        }

        /** @var \Crm\Model\Table\ContactsTable $ContactsTable */
        $ContactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contact = $ContactsTable->find()
            ->select(['id'])
            ->where([
                'Contacts.owner_id' => $ownerId,
                'Contacts.tax_no' => $taxNo,
            ])
            ->first();

        return $contact?->id;
    }

    /**
     * Find a VAT level entity matching the given percentage.
     *
     * @param iterable<\Documents\Model\Entity\Vat> $vatLevels Available VAT levels.
     * @param float $percent The VAT percentage to match.
     * @return \Documents\Model\Entity\Vat|null
     */
    private function _findVatByPercent(iterable $vatLevels, float $percent): ?Vat
    {
        foreach ($vatLevels as $vat) {
            if (abs((float)$vat->percent - $percent) < 0.01) {
                return $vat;
            }
        }

        return null;
    }
}
