<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\Core\Plugin;
use Cake\Utility\Xml;
use DOMDocument;
use Exception;
use SimpleXMLElement;

/**
 * EslogImport - Parse eSlog 2.0 XML invoices into structured data.
 *
 * Uses CakePHP Xml utility for parsing and XSD validation.
 */
class EslogImport
{
    /**
     * @var string|null Last error message.
     */
    public ?string $lastError = null;

    /**
     * Map of eslog20 unit codes to internal unit strings.
     *
     * @var array<string, string>
     */
    private array $unitMap = [
        'CMT' => 'cm',
        'DAY' => 'dan',
        'GRM' => 'g',
        'HUR' => 'ura',
        'KGM' => 'kg',
        'KTM' => 'km',
        'LTR' => 'l',
        'MGM' => 'mg',
        'MIN' => 'min',
        'MMT' => 'mm',
        'MON' => 'mes',
        'MTR' => 'm',
        'C62' => 'pcs',
        'PCE' => 'pcs',
    ];

    /**
     * Parse eslog20 XML string and return structured invoice data.
     *
     * @param string $xmlString Raw XML content.
     * @return array<string, mixed>|null Parsed invoice data or null on error.
     */
    public function parse(string $xmlString): ?array
    {
        if (trim($xmlString) === '') {
            $this->lastError = 'Empty XML content';

            return null;
        }

        try {
            // Use CakePHP Xml utility to build a SimpleXMLElement explicitly
            $simpleXml = Xml::build($xmlString, ['return' => 'simplexml']);
            if (!$simpleXml instanceof SimpleXMLElement) {
                $this->lastError = 'Failed to parse XML content';

                return null;
            }

            // Convert SimpleXMLElement to array for easier traversal
            $data = $this->simpleXmlToArray($simpleXml);
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();

            return null;
        }

        // Find M_INVOIC element
        if (!isset($data['M_INVOIC'])) {
            $this->lastError = 'Invalid eSlog 2.0 structure: missing M_INVOIC';

            return null;
        }

        $mInvoic = $data['M_INVOIC'];

        // Handle multiple M_INVOIC - take first
        if (is_array($mInvoic) && isset($mInvoic[0])) {
            $mInvoic = $mInvoic[0];
        }

        $result = [
            'invoice' => [],
            'issuer' => [],
            'receiver' => [],
            'buyer' => [],
            'items' => [],
        ];

        // Parse invoice header fields
        $result['invoice'] = $this->parseInvoiceHeader($mInvoic);

        // Parse parties (BY=buyer, SE=seller/issuer)
        $parties = $this->parseParties($mInvoic);
        $result['issuer'] = $parties['SE'] ?? [];
        $result['receiver'] = $parties['IV'] ?? $parties['BY'] ?? [];
        $result['buyer'] = $parties['BY'] ?? [];

        // Parse line items
        $result['items'] = $this->parseItems($mInvoic);

        return $result;
    }

    /**
     * Convert SimpleXMLElement to array using CakePHP Xml utility internally.
     *
     * @param \SimpleXMLElement $xml SimpleXML element.
     * @return array<string, mixed>
     */
    private function simpleXmlToArray(SimpleXMLElement $xml): array
    {
        // json_encode handles SimpleXMLElement natively, preserving structure
        $json = json_encode($xml);
        if ($json === false) {
            return [];
        }

        return (array)json_decode($json, true);
    }

    /**
     * Parse invoice header fields from M_INVOIC section.
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return array<string, mixed>
     */
    private function parseInvoiceHeader(array $mInvoic): array
    {
        $invoice = [];

        // Document number from S_BGM/C_C106/D_1004
        if (!empty($mInvoic['S_BGM']['C_C106']['D_1004'])) {
            $invoice['no'] = (string)$mInvoic['S_BGM']['C_C106']['D_1004'];
        }

        // Free-text title from S_FTX with qualifier AAI
        $title = $this->extractTitle($mInvoic);
        if ($title !== null) {
            $invoice['title'] = $title;
        }

        // Parse dates from S_DTM elements
        $dates = $this->parseDates($mInvoic);
        if (!empty($dates)) {
            $invoice += $dates;
        }

        // Payment reference from G_SG1/S_RFF
        $pmtRef = $this->extractPaymentReference($mInvoic);
        if (!empty($pmtRef)) {
            $invoice += $pmtRef;
        }

        // Parse totals from G_SG50
        $totals = $this->parseTotals($mInvoic);
        if (!empty($totals)) {
            $invoice += $totals;
        }

        return $invoice;
    }

    /**
     * Extract the free-text invoice title from an S_FTX segment with qualifier AAI.
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return string|null
     */
    private function extractTitle(array $mInvoic): ?string
    {
        if (empty($mInvoic['S_FTX'])) {
            return null;
        }

        foreach ($this->normalizeArray($mInvoic['S_FTX']) as $ftx) {
            if (!is_array($ftx) || (string)($ftx['D_4451'] ?? '') !== 'AAI') {
                continue;
            }
            $text = $ftx['C_C108']['D_4440'] ?? '';
            if (is_string($text) && trim($text) !== '') {
                return trim($text);
            }
        }

        return null;
    }

    /**
     * Parse S_DTM date elements.
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return array<string, mixed>
     */
    private function parseDates(array $mInvoic): array
    {
        $dates = [];

        if (empty($mInvoic['S_DTM'])) {
            return $dates;
        }

        $dtmElements = $this->normalizeArray($mInvoic['S_DTM']);

        foreach ($dtmElements as $dtm) {
            if (empty($dtm['C_C507']['D_2005']) || empty($dtm['C_C507']['D_2380'])) {
                continue;
            }

            $code = (string)$dtm['C_C507']['D_2005'];
            $value = (string)$dtm['C_C507']['D_2380'];

            switch ($code) {
                case '137': // Invoice issue date
                    $dates['dat_issue'] = $value;
                    break;
                case '35': // Service/delivery date
                    $dates['dat_service'] = $value;
                    break;
                case '13': // Due/expiry date (from G_SG8)
                    $dates['dat_expire'] = $value;
                    break;
            }
        }

        // Also check G_SG8 for expiry date (code 13)
        if (!empty($mInvoic['G_SG8']['S_DTM'])) {
            $sg8DtmElements = $this->normalizeArray($mInvoic['G_SG8']['S_DTM']);
            foreach ($sg8DtmElements as $dtm) {
                if (!empty($dtm['C_C507']['D_2005']) && !empty($dtm['C_C507']['D_2380'])) {
                    $code = (string)$dtm['C_C507']['D_2005'];
                    if ($code === '13') {
                        $dates['dat_expire'] = (string)$dtm['C_C507']['D_2380'];
                    }
                }
            }
        }

        return $dates;
    }

    /**
     * Extract payment reference from G_SG1.
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return array<string, mixed>
     */
    private function extractPaymentReference(array $mInvoic): array
    {
        $result = [];

        if (empty($mInvoic['G_SG1']['S_RFF']['C_C506'])) {
            return $result;
        }

        $cC506 = $mInvoic['G_SG1']['S_RFF']['C_C506'];
        $qualifier = (string)($cC506['D_1153'] ?? '');

        if ($qualifier === 'PQ' && !empty($cC506['D_1154'])) {
            $ref = (string)$cC506['D_1154'];
            // Parse payment reference: first 2 chars = type, next 2 = module, rest = ref
            if (strlen($ref) >= 4) {
                $result['pmt_type'] = substr($ref, 0, 2);
                $result['pmt_module'] = substr($ref, 2, 2);
                $result['pmt_ref'] = substr($ref, 4);
            } else {
                $result['pmt_ref'] = $ref;
            }
        }

        return $result;
    }

    /**
     * Parse total amounts from G_SG50.
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return array<string, mixed>
     */
    private function parseTotals(array $mInvoic): array
    {
        $totals = [];

        if (empty($mInvoic['G_SG50'])) {
            return $totals;
        }

        $sg50Elements = $this->normalizeArray($mInvoic['G_SG50']);

        foreach ($sg50Elements as $element) {
            if (empty($element['S_MOA']['C_C516'])) {
                continue;
            }

            $cC516 = $element['S_MOA']['C_C516'];
            $code = (string)($cC516['D_5025'] ?? '');
            $amount = (float)($cC516['D_5004'] ?? 0);

            switch ($code) {
                case '389': // Amount excluding VAT
                    $totals['net_total'] = $amount;
                    break;
                case '388': // Total amount including VAT
                    $totals['total'] = $amount;
                    break;
                case '9': // Amount due/payable
                    if (!isset($totals['total'])) {
                        $totals['total'] = $amount;
                    }
                    break;
            }
        }

        return $totals;
    }

    /**
     * Parse party information (buyer/seller/issuer/receiver).
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return array<string, array<string, mixed>>
     */
    private function parseParties(array $mInvoic): array
    {
        $parties = [];

        if (empty($mInvoic['G_SG2'])) {
            return $parties;
        }

        $sg2Elements = $this->normalizeArray($mInvoic['G_SG2']);

        foreach ($sg2Elements as $sg2) {
            if (empty($sg2['S_NAD'])) {
                continue;
            }

            $nad = $sg2['S_NAD'];
            $roleCode = (string)($nad['D_3035'] ?? '');

            if ($roleCode === '') {
                continue;
            }

            $party = [];

            // Title from C_C080/D_3036
            if (!empty($nad['C_C080']['D_3036'])) {
                $party['title'] = (string)$nad['C_C080']['D_3036'];
            }

            // Street from C_C059/D_3042
            if (!empty($nad['C_C059']['D_3042'])) {
                $party['street'] = (string)$nad['C_C059']['D_3042'];
            }

            // City from D_3164
            if (!empty($nad['D_3164'])) {
                $party['city'] = (string)$nad['D_3164'];
            }

            // Zip from D_3251
            if (!empty($nad['D_3251'])) {
                $party['zip'] = (string)$nad['D_3251'];
            }

            // Country code from D_3207
            if (!empty($nad['D_3207'])) {
                $party['country_code'] = (string)$nad['D_3207'];
            }

            // Tax number from G_SG3 with qualifier VA
            $taxNo = $this->extractTaxNumber($sg2);
            if ($taxNo !== null) {
                $party['tax_no'] = $taxNo;
            }

            // IBAN from S_FII
            $iban = $this->extractIban($sg2);
            if ($iban !== null) {
                $party['iban'] = $iban;
            }

            // BIC/SWIFT from S_FII/C_C088/D_3433
            if (!empty($sg2['S_FII']['C_C088']['D_3433'])) {
                $bic = (string)$sg2['S_FII']['C_C088']['D_3433'];
                // Pad might be used, strip padding X chars
                $party['bic'] = rtrim($bic, 'X');
            }

            // Email from G_SG5/S_COM/C_C076/D_3148
            if (!empty($sg2['G_SG5']['S_COM']['C_C076']['D_3148'])) {
                $party['email'] = (string)$sg2['G_SG5']['S_COM']['C_C076']['D_3148'];
            }

            $parties[$roleCode] = $party;
        }

        return $parties;
    }

    /**
     * Extract tax number from G_SG3 references.
     *
     * @param array<string, mixed> $sg2 The G_SG2 element data.
     * @return string|null
     */
    private function extractTaxNumber(array $sg2): ?string
    {
        if (empty($sg2['G_SG3'])) {
            return null;
        }

        $sg3Elements = $this->normalizeArray($sg2['G_SG3']);

        foreach ($sg3Elements as $sg3) {
            if (empty($sg3['S_RFF']['C_C506'])) {
                continue;
            }

            $cC506 = $sg3['S_RFF']['C_C506'];
            $qualifier = (string)($cC506['D_1153'] ?? '');

            if ($qualifier === 'VA' && !empty($cC506['D_1154'])) {
                return (string)$cC506['D_1154'];
            }
        }

        return null;
    }

    /**
     * Extract IBAN from S_FII.
     *
     * @param array<string, mixed> $sg2 The G_SG2 element data.
     * @return string|null
     */
    private function extractIban(array $sg2): ?string
    {
        if (empty($sg2['S_FII']['C_C078']['D_3194'])) {
            return null;
        }

        return (string)$sg2['S_FII']['C_C078']['D_3194'];
    }

    /**
     * Parse line items from G_SG26 elements.
     *
     * @param array<string, mixed> $mInvoic The M_INVOIC data block.
     * @return array<array<string, mixed>>
     */
    private function parseItems(array $mInvoic): array
    {
        $items = [];

        if (empty($mInvoic['G_SG26'])) {
            return $items;
        }

        $sg26Elements = $this->normalizeArray($mInvoic['G_SG26']);

        foreach ($sg26Elements as $sg26) {
            $item = [];

            // Description from S_IMD/C_C273/D_7008
            if (!empty($sg26['S_IMD']['C_C273']['D_7008'])) {
                $item['descript'] = (string)$sg26['S_IMD']['C_C273']['D_7008'];
            }

            // Quantity from S_QTY/C_C186
            if (!empty($sg26['S_QTY']['C_C186'])) {
                $cC186 = $sg26['S_QTY']['C_C186'];
                if (!empty($cC186['D_6060'])) {
                    $item['qty'] = (float)$cC186['D_6060'];
                }
                if (!empty($cC186['D_6411'])) {
                    $unitCode = (string)$cC186['D_6411'];
                    $item['unit'] = $this->unitMap[$unitCode] ?? 'pcs';
                }
            }

            // Price from G_SG29/S_PRI/C_C509 with qualifier AAA
            if (!empty($sg26['G_SG29'])) {
                $sg29Elements = $this->normalizeArray($sg26['G_SG29']);

                foreach ($sg29Elements as $sg29) {
                    if (empty($sg29['S_PRI']['C_C509'])) {
                        continue;
                    }

                    $cC509 = $sg29['S_PRI']['C_C509'];
                    $qualifier = (string)($cC509['D_5125'] ?? '');

                    if ($qualifier === 'AAA' && !empty($cC509['D_5118'])) {
                        $item['price'] = (float)$cC509['D_5118'];
                    } elseif ($qualifier === 'AAB' && !empty($cC509['D_5118'])) {
                        // Line total - can be used to derive discount if needed
                        $lineTotal = (float)$cC509['D_5118'];
                        if (isset($item['qty']) && isset($item['price'])) {
                            $calculated = round((float)$item['qty'] * (float)$item['price'], 2);
                            if ($calculated > 0 && $lineTotal < $calculated) {
                                // There might be a discount
                                $discountPercent = round((1 - $lineTotal / $calculated) * 100, 2);
                                if ($discountPercent > 0) {
                                    $item['discount'] = $discountPercent;
                                }
                            }
                        }
                    }
                }
            }

            // VAT from G_SG34/S_TAX
            if (!empty($sg26['G_SG34']['S_TAX'])) {
                $tax = $sg26['G_SG34']['S_TAX'];
                if (!empty($tax['C_C243']['D_5278'])) {
                    $item['vat_percent'] = (float)$tax['C_C243']['D_5278'];
                }
                // Tax category from D_5305
                if (!empty($tax['D_5305'])) {
                    $item['vat_category'] = (string)$tax['D_5305'];
                }
            }

            // Set defaults for missing fields
            $item += [
                'qty' => 1,
                'unit' => 'pcs',
                'price' => 0,
                'discount' => 0,
                'vat_percent' => 0,
            ];

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Normalize a value that may be a single element or an array of elements.
     * Ensures we always get an indexed array for iteration.
     *
     * @param mixed $value The XML node data (string, array, or empty).
     * @return array<mixed>
     */
    private function normalizeArray(mixed $value): array
    {
        if (empty($value)) {
            return [];
        }

        // If it's already an array with numeric keys, it's a list of elements
        if (is_array($value) && array_keys($value) === range(0, count($value) - 1)) {
            return $value;
        }

        // Wrap single element in array
        return [$value];
    }

    /**
     * Validate eSlog20 XML against XSD schema.
     *
     * @param string $xmlString Raw XML content.
     * @return array<string> Array of validation errors, empty if valid.
     */
    public function validate(string $xmlString): array
    {
        libxml_use_internal_errors(true);

        $dom = new DOMDocument();
        if (!$dom->loadXML($xmlString, LIBXML_NOCDATA | LIBXML_NONET)) {
            $errors = libxml_get_errors();
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = trim((string)$error->message);
            }
            libxml_clear_errors();

            return $messages;
        }
        libxml_clear_errors();

        $xsdPath = Plugin::path('Documents') . 'webroot' . DS . 'schema' . DS . 'eSLOG20_INVOIC_v200.xsd';

        if (!file_exists($xsdPath)) {
            return ['XSD schema file not found: ' . $xsdPath];
        }

        if (!$dom->schemaValidate($xsdPath)) {
            $errors = libxml_get_errors();
            $messages = [];
            foreach ($errors as $error) {
                $messages[] = trim((string)$error->message);
            }
            libxml_clear_errors();

            return $messages;
        }

        return [];
    }
}
