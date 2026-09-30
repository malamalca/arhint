<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\I18n\DateTime;
use Cake\Utility\Text;
use DateTimeInterface;
use DateTimeZone;
use Documents\Model\Entity\Invoice;
use Documents\Model\Entity\InvoicesTaxConfirmation;
use Documents\Model\Entity\TaxPremise;
use DOMDocument;
use DOMElement;

/**
 * Builds FURS (fiscal verification of invoices) SOAP requests.
 */
class FursXml
{
    public const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    public const NS_FU = 'http://www.fu.gov.si/';
    public const NS_DS = 'http://www.w3.org/2000/09/xmldsig#';

    public const TIMEZONE = 'Europe/Ljubljana';

    /**
     * Normalize tax number to 8 digits (strips SI prefix and separators).
     *
     * @param string|null $taxNo Tax number.
     * @return string|null Null if not a valid 8 digit number.
     */
    public static function normalizeTaxNo(?string $taxNo): ?string
    {
        $taxNo = preg_replace('/^\s*SI/i', '', (string)$taxNo);
        $taxNo = preg_replace('/\D/', '', (string)$taxNo);

        return strlen((string)$taxNo) === 8 ? $taxNo : null;
    }

    /**
     * Convert date time to local (FURS) time.
     *
     * @param \DateTimeInterface $date Date time.
     * @return \Cake\I18n\DateTime
     */
    public static function localTime(DateTimeInterface $date): DateTime
    {
        return DateTime::createFromInterface($date)->setTimezone(new DateTimeZone(self::TIMEZONE));
    }

    /**
     * Amount in FURS format.
     *
     * @param string|float|int|null $amount Amount.
     * @return string
     */
    public static function amount(float|int|string|null $amount): string
    {
        return number_format((float)$amount, 2, '.', '');
    }

    /**
     * String that is signed to get ZOI.
     *
     * @param \Documents\Model\Entity\InvoicesTaxConfirmation $confirmation Confirmation entity.
     * @param \Documents\Model\Entity\Invoice $invoice Invoice entity.
     * @return string
     */
    public static function zoiString(InvoicesTaxConfirmation $confirmation, Invoice $invoice): string
    {
        return (string)$confirmation->issuer_taxno .
            self::localTime($confirmation->issued_at)->format('d.m.Y H:i:s') .
            (string)$invoice->counter .
            (string)$confirmation->bp_no .
            (string)$confirmation->device_no .
            self::amount($invoice->total);
    }

    /**
     * Signed-ready InvoiceRequest envelope.
     *
     * @param \Documents\Model\Entity\Invoice $invoice Invoice with buyer and invoices_items.
     * @param \Documents\Model\Entity\InvoicesTaxConfirmation $confirmation Confirmation entity with zoi.
     * @param bool $subsequent Mark as subsequent submit (resend after failed confirmation).
     * @return string
     */
    public static function invoice(
        Invoice $invoice,
        InvoicesTaxConfirmation $confirmation,
        bool $subsequent = false,
    ): string {
        [$doc, $body] = self::envelope();
        $request = self::el($doc, $body, 'InvoiceRequest');
        $request->setAttribute('Id', 'data');
        self::header($doc, $request);

        $inv = self::el($doc, $request, 'Invoice');
        self::el($doc, $inv, 'TaxNumber', (string)$confirmation->issuer_taxno);
        self::el($doc, $inv, 'IssueDateTime', self::localTime($confirmation->issued_at)->format('Y-m-d\TH:i:s'));
        // C - number is assigned centrally for the business premise
        self::el($doc, $inv, 'NumberingStructure', 'C');

        $identifier = self::el($doc, $inv, 'InvoiceIdentifier');
        self::el($doc, $identifier, 'BusinessPremiseID', (string)$confirmation->bp_no);
        self::el($doc, $identifier, 'ElectronicDeviceID', (string)$confirmation->device_no);
        self::el($doc, $identifier, 'InvoiceNumber', (string)$invoice->counter);

        $buyerTaxNo = trim((string)($invoice->buyer->tax_no ?? ''));
        if ($buyerTaxNo !== '') {
            self::el($doc, $inv, 'CustomerVATNumber', $buyerTaxNo);
        }

        self::el($doc, $inv, 'InvoiceAmount', self::amount($invoice->total));
        self::el($doc, $inv, 'PaymentAmount', self::amount($invoice->total));

        $taxes = self::el($doc, $inv, 'TaxesPerSeller');
        $spec = self::taxSpecification($invoice);
        foreach ($spec['vat'] as $percent => $vat) {
            $vatEl = self::el($doc, $taxes, 'VAT');
            self::el($doc, $vatEl, 'TaxRate', self::amount($percent));
            self::el($doc, $vatEl, 'TaxableAmount', self::amount($vat['base']));
            self::el($doc, $vatEl, 'TaxAmount', self::amount($vat['amount']));
        }
        if ($spec['exempt'] > 0) {
            self::el($doc, $taxes, 'ExemptVATTaxableAmount', self::amount($spec['exempt']));
        }
        if ($spec['nontaxable'] > 0) {
            self::el($doc, $taxes, 'NontaxableAmount', self::amount($spec['nontaxable']));
        }

        if (!empty($confirmation->operator_taxno)) {
            self::el($doc, $inv, 'OperatorTaxNumber', (string)$confirmation->operator_taxno);
        }
        self::el($doc, $inv, 'ProtectedID', (string)$confirmation->zoi);
        if ($subsequent) {
            self::el($doc, $inv, 'SubsequentSubmit', 'true');
        }

        return (string)$doc->saveXML();
    }

    /**
     * Group invoice items by VAT.
     *
     * @param \Documents\Model\Entity\Invoice $invoice Invoice with invoices_items.
     * @return array{vat: array<string, array{base: float, amount: float}>, exempt: float, nontaxable: float}
     */
    public static function taxSpecification(Invoice $invoice): array
    {
        $ret = ['vat' => [], 'exempt' => 0.0, 'nontaxable' => 0.0];
        foreach ((array)$invoice->invoices_items as $item) {
            if (empty($item->vat_id)) {
                $ret['exempt'] += $item->net_total;
            } elseif ((float)$item->vat_percent == 0) {
                $ret['nontaxable'] += $item->net_total;
            } else {
                $key = self::amount($item->vat_percent);
                $ret['vat'][$key] ??= ['base' => 0.0, 'amount' => 0.0];
                $ret['vat'][$key]['base'] += $item->net_total;
                $ret['vat'][$key]['amount'] += $item->tax_total;
            }
        }
        ksort($ret['vat'], SORT_NUMERIC);

        return $ret;
    }

    /**
     * BusinessPremiseRequest envelope.
     *
     * @param \Documents\Model\Entity\TaxPremise $premise Premise entity.
     * @param string $issuerTaxNo Issuer's 8 digit tax number.
     * @return string
     */
    public static function premise(TaxPremise $premise, string $issuerTaxNo): string
    {
        [$doc, $body] = self::envelope();
        $request = self::el($doc, $body, 'BusinessPremiseRequest');
        $request->setAttribute('Id', 'data');
        self::header($doc, $request);

        $bp = self::el($doc, $request, 'BusinessPremise');
        self::el($doc, $bp, 'TaxNumber', $issuerTaxNo);
        self::el($doc, $bp, 'BusinessPremiseID', (string)$premise->no);

        $identifier = self::el($doc, $bp, 'BPIdentifier');
        if ($premise->kind === 'RL') {
            $realEstate = self::el($doc, $identifier, 'RealEstateBP');
            $property = self::el($doc, $realEstate, 'PropertyID');
            self::el($doc, $property, 'CadastralNumber', (string)$premise->casadral_number);
            self::el($doc, $property, 'BuildingNumber', (string)$premise->building_number);
            self::el($doc, $property, 'BuildingSectionNumber', (string)$premise->building_section_number);

            $address = self::el($doc, $realEstate, 'Address');
            self::el($doc, $address, 'Street', (string)$premise->street);
            self::el($doc, $address, 'HouseNumber', (string)$premise->house_number);
            if (!empty($premise->house_number_additional)) {
                self::el($doc, $address, 'HouseNumberAdditional', (string)$premise->house_number_additional);
            }
            self::el($doc, $address, 'Community', (string)$premise->community);
            self::el($doc, $address, 'City', (string)$premise->city);
            self::el($doc, $address, 'PostalCode', (string)$premise->postal_code);
        } else {
            self::el($doc, $identifier, 'PremiseType', (string)$premise->mo_type);
        }

        self::el($doc, $bp, 'ValidityDate', $premise->validity_date->format('Y-m-d'));
        if ($premise->closed) {
            self::el($doc, $bp, 'ClosingTag', 'Z');
        }
        $supplier = self::el($doc, $bp, 'SoftwareSupplier');
        self::el($doc, $supplier, 'TaxNumber', (string)$premise->sw_taxno);
        if (!empty($premise->notes)) {
            self::el($doc, $bp, 'SpecialNotes', mb_substr((string)$premise->notes, 0, 1000));
        }

        return (string)$doc->saveXML();
    }

    /**
     * Create SOAP envelope.
     *
     * @return array{0: \DOMDocument, 1: \DOMElement} Document and soap body.
     */
    private static function envelope(): array
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $envelope = $doc->createElementNS(self::NS_SOAP, 'soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:fu', self::NS_FU);
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ds', self::NS_DS);
        $doc->appendChild($envelope);
        $body = $doc->createElementNS(self::NS_SOAP, 'soapenv:Body');
        $envelope->appendChild($body);

        return [$doc, $body];
    }

    /**
     * Create fu:Header.
     *
     * @param \DOMDocument $doc Document.
     * @param \DOMElement $request Request element.
     * @return void
     */
    private static function header(DOMDocument $doc, DOMElement $request): void
    {
        $header = self::el($doc, $request, 'Header');
        self::el($doc, $header, 'MessageID', Text::uuid());
        self::el($doc, $header, 'DateTime', self::localTime(new DateTime())->format('Y-m-d\TH:i:s'));
    }

    /**
     * Append fu:* element.
     *
     * @param \DOMDocument $doc Document.
     * @param \DOMElement $parent Parent element.
     * @param string $name Local element name.
     * @param string|null $value Text value.
     * @return \DOMElement
     */
    private static function el(DOMDocument $doc, DOMElement $parent, string $name, ?string $value = null): DOMElement
    {
        $el = $doc->createElementNS(self::NS_FU, 'fu:' . $name);
        if ($value !== null) {
            $el->appendChild($doc->createTextNode($value));
        }
        $parent->appendChild($el);

        return $el;
    }
}
