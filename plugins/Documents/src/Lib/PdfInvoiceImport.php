<?php
declare(strict_types=1);

namespace Documents\Lib;

use App\Lib\AIAssistant;
use Cake\Log\Log;
use Throwable;

/**
 * PdfInvoiceImport - Convert a PDF invoice into eSlog 2.0 XML using a multimodal LLM.
 *
 * The PDF is sent to the configured AI provider (see {@see \App\Lib\AIAssistant}) together
 * with a strict prompt and an eSlog 2.0 template. The model returns eSlog 2.0 INVOIC XML
 * which is then processed by the regular {@see \Documents\Lib\EslogImport} pipeline, exactly
 * as an uploaded XML file would be.
 */
class PdfInvoiceImport
{
    /**
     * Sentinel the model is asked to return when it cannot read the invoice.
     */
    private const CANNOT_PARSE = 'CANNOT_PARSE';

    /**
     * @var string|null Last error message, set when {@see convert()} returns null.
     */
    public ?string $lastError = null;

    /**
     * @var \App\Lib\AIAssistant The AI assistant used for the completion request.
     */
    private AIAssistant $assistant;

    /**
     * @var int Request timeout in seconds.
     */
    private int $timeout;

    /**
     * Instructions describing the conversion task and the exact eSlog 2.0 mapping.
     */
    private const SYSTEM_PROMPT = <<<'TXT'
You convert business invoices (faktura/račun) into Slovenian e-SLOG 2.0 INVOIC XML.

You will be given an invoice as a PDF. Extract every field you can and produce a single
e-SLOG 2.0 XML document that follows the template below EXACTLY (same elements, same order,
same namespaces). Fill the values from the invoice. Omit a block only when the data is truly
absent from the invoice.

Output rules:
- Output ONLY the raw XML. No markdown, no code fences, no commentary before or after.
- If the document is not an invoice, or you cannot read enough to identify the supplier,
  the amounts and at least one line item, output exactly: <error>CANNOT_PARSE</error>

Field mapping (UN/EDIFACT codes used by e-SLOG):
- Invoice number  -> S_BGM/C_C106/D_1004
- Issue date      -> S_DTM with C_C507/D_2005=137, D_2380=YYYY-MM-DD
- Service date    -> S_DTM with C_C507/D_2005=35  (date of supply / "datum opr. dobave")
- Due date        -> G_SG8/S_DTM with C_C507/D_2005=13 ("datum valute" / payment due)
- Payment reference (Slovenian model + sklic) -> G_SG1/S_RFF/C_C506 with D_1153=PQ and
  D_1154 = "SI" + two-digit model + reference, concatenated with no spaces
  (e.g. model 12, sklic 2636000051000 => SI122636000051000).
- Parties are in G_SG2 blocks, one per party, identified by S_NAD/D_3035:
    SE = seller / supplier (izdajatelj, the company that issued the invoice)
    BY = buyer / payer (Plačnik / Prevzemnik / Kupec)
  Per party: name C_C080/D_3036, street C_C059/D_3042, city D_3164, post code D_3251,
  country code D_3207 (ISO, e.g. SI), VAT/tax number in G_SG3/S_RFF/C_C506 with D_1153=VA,
  IBAN in S_FII/C_C078/D_3194, BIC in S_FII/C_C088/D_3433, e-mail in
  G_SG5/S_COM/C_C076/D_3148.
- Each line item is a G_SG26 block:
    description S_IMD/C_C273/D_7008, quantity S_QTY/C_C186/D_6060, unit code S_QTY/C_C186/D_6411
    (e.g. C62=piece, HUR=hour, KGM=kg, MTR=m, DAY=day, MON=month), unit price
    G_SG29/S_PRI/C_C509 with D_5125=AAA and D_5118=price, VAT rate G_SG34/S_TAX/C_C243/D_5278.
- Totals in G_SG50/S_MOA/C_C516 with D_5025 code and D_5004 amount:
    389 = total without VAT (osnova / net), 388 = total with VAT (za plačilo / gross).
- Use a dot as the decimal separator and no thousands separators (e.g. 1234.56).
- Keep the structural elements that carry no invoice data exactly as in the template: the
  S_UNH message header, the G_SG7 currency block, the S_PAT/D_4279 in G_SG8 and the
  S_QTY/C_C186/D_6063 quantity qualifier. They are required by the schema.

Template:
<?xml version="1.0" encoding="UTF-8"?>
<Invoice xmlns="urn:eslog:2.00" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <M_INVOIC Id="data">
    <S_UNH>
      <D_0062>1</D_0062>
      <C_S009><D_0065>INVOIC</D_0065><D_0052>D</D_0052><D_0054>01B</D_0054><D_0051>UN</D_0051></C_S009>
    </S_UNH>
    <S_BGM>
      <C_C002><D_1001>380</D_1001></C_C002>
      <C_C106><D_1004>INVOICE-NUMBER</D_1004></C_C106>
    </S_BGM>
    <S_DTM><C_C507><D_2005>137</D_2005><D_2380>YYYY-MM-DD</D_2380></C_C507></S_DTM>
    <S_DTM><C_C507><D_2005>35</D_2005><D_2380>YYYY-MM-DD</D_2380></C_C507></S_DTM>
    <G_SG1><S_RFF><C_C506><D_1153>PQ</D_1153><D_1154>SI12...</D_1154></C_C506></S_RFF></G_SG1>
    <G_SG2>
      <S_NAD>
        <D_3035>SE</D_3035>
        <C_C080><D_3036>SELLER NAME</D_3036></C_C080>
        <C_C059><D_3042>STREET</D_3042></C_C059>
        <D_3164>CITY</D_3164><D_3251>POSTCODE</D_3251><D_3207>SI</D_3207>
      </S_NAD>
      <S_FII><D_3035>RB</D_3035><C_C078><D_3194>IBAN</D_3194></C_C078></S_FII>
      <G_SG3><S_RFF><C_C506><D_1153>VA</D_1153><D_1154>SI12345678</D_1154></C_C506></S_RFF></G_SG3>
    </G_SG2>
    <G_SG2>
      <S_NAD>
        <D_3035>BY</D_3035>
        <C_C080><D_3036>BUYER NAME</D_3036></C_C080>
        <C_C059><D_3042>STREET</D_3042></C_C059>
        <D_3164>CITY</D_3164><D_3251>POSTCODE</D_3251><D_3207>SI</D_3207>
      </S_NAD>
      <G_SG3><S_RFF><C_C506><D_1153>VA</D_1153><D_1154>SI87654321</D_1154></C_C506></S_RFF></G_SG3>
    </G_SG2>
    <G_SG7><S_CUX><C_C504><D_6347>2</D_6347><D_6345>EUR</D_6345></C_C504></S_CUX></G_SG7>
    <G_SG8>
      <S_PAT><D_4279>1</D_4279></S_PAT>
      <S_DTM><C_C507><D_2005>13</D_2005><D_2380>YYYY-MM-DD</D_2380></C_C507></S_DTM>
    </G_SG8>
    <G_SG26>
      <S_LIN><D_1082>1</D_1082></S_LIN>
      <S_IMD><C_C273><D_7008>ITEM DESCRIPTION</D_7008></C_C273></S_IMD>
      <S_QTY><C_C186><D_6063>47</D_6063><D_6060>1.00</D_6060><D_6411>C62</D_6411></C_C186></S_QTY>
      <G_SG29><S_PRI><C_C509><D_5125>AAA</D_5125><D_5118>0.00</D_5118></C_C509></S_PRI></G_SG29>
      <G_SG34><S_TAX><D_5283>7</D_5283><C_C243><D_5278>22.0</D_5278></C_C243><D_5305>S</D_5305></S_TAX></G_SG34>
    </G_SG26>
    <G_SG50><S_MOA><C_C516><D_5025>389</D_5025><D_5004>0.00</D_5004></C_C516></S_MOA></G_SG50>
    <G_SG50><S_MOA><C_C516><D_5025>388</D_5025><D_5004>0.00</D_5004></C_C516></S_MOA></G_SG50>
  </M_INVOIC>
</Invoice>
TXT;

    /**
     * @param \App\Lib\AIAssistant $assistant AI assistant configured for the current user.
     * @param int $timeout Request timeout in seconds.
     */
    public function __construct(AIAssistant $assistant, int $timeout = 150)
    {
        $this->assistant = $assistant;
        $this->timeout = $timeout;
    }

    /**
     * Convert raw PDF bytes into eSlog 2.0 XML.
     *
     * @param string $pdfContent Raw PDF file content.
     * @param string $filename File name sent to the model (for context only).
     * @return string|null eSlog 2.0 XML on success, or null on failure (see {@see $lastError}).
     */
    public function convert(string $pdfContent, string $filename = 'invoice.pdf'): ?string
    {
        $this->lastError = null;

        if (trim($pdfContent) === '') {
            $this->lastError = 'Empty PDF content.';

            return null;
        }

        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            ['role' => 'user', 'content' => [
                [
                    'type' => 'text',
                    'text' => 'Convert the attached invoice PDF into e-SLOG 2.0 XML '
                        . 'following the instructions and template exactly. Output only the XML.',
                ],
                [
                    'type' => 'file',
                    'file' => [
                        'filename' => $filename,
                        'file_data' => 'data:application/pdf;base64,' . base64_encode($pdfContent),
                    ],
                ],
            ]],
        ];

        try {
            $raw = $this->assistant->complete($messages, $this->timeout);
        } catch (Throwable $e) {
            Log::error(
                'PdfInvoiceImport AI request failed: ' . $e->getMessage(),
                ['scope' => ['ai'], 'file' => $e->getFile() . ':' . $e->getLine()],
            );
            $this->lastError = 'The AI service could not be reached. Please try again later.';

            return null;
        }

        $xml = $this->extractXml($raw);
        if ($xml === null) {
            $this->lastError = 'The AI could not extract invoice data from the uploaded PDF.';

            return null;
        }

        return $xml;
    }

    /**
     * Pull a clean eSlog XML document out of the model's raw response.
     *
     * Strips markdown fences, honours the CANNOT_PARSE sentinel and trims any text the model
     * may have added around the document.
     *
     * @param string $raw Raw model output.
     * @return string|null The XML document, or null when none could be recovered.
     */
    private function extractXml(string $raw): ?string
    {
        $content = trim($raw);
        if ($content === '' || str_contains($content, self::CANNOT_PARSE)) {
            return null;
        }

        // Strip surrounding markdown code fences some models add despite instructions.
        $content = (string)preg_replace('/^```(?:xml)?\s*/i', '', $content);
        $content = (string)preg_replace('/\s*```$/', '', $content);

        // Isolate the <Invoice>...</Invoice> document, ignoring any stray prose.
        if (preg_match('/<Invoice\b[\s\S]*<\/Invoice>/', $content, $matches) !== 1) {
            return null;
        }

        $xml = trim($matches[0]);

        // Re-attach the XML declaration when the model dropped it.
        if (!str_starts_with($content, '<?xml') && !str_starts_with($xml, '<?xml')) {
            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $xml;
        } elseif (preg_match('/<\?xml[\s\S]*?\?>/', $content, $declMatches) === 1) {
            $xml = trim($declMatches[0]) . "\n" . $xml;
        }

        return $xml;
    }
}
