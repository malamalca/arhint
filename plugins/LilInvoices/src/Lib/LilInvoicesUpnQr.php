<?php
declare(strict_types=1);

namespace LilInvoices\Lib;

use Cake\ORM\TableRegistry;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class LilInvoicesUpnQr
{
    /**
     * Generates UpnQr code for specified invoice
     *
     * @param string $invoiceId Invoice id
     * @return string
     */
    public static function generateUpnQr($invoiceId)
    {
        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        $invoice = $InvoicesTable->get($invoiceId, ['contain' => ['Receivers', 'Issuers']]);

        $qrDelim = chr(10);
        $qrData = [
            'vodilni_slog' => 'UPNQR',
            'iban_placnika' => '',
            'polog' => '',
            'dvig' => '',
            'referenca_placnika' => '',
            'ime_placnika' => mb_substr($invoice->receiver->title, 0, 33),
            'ulica_placnika' => mb_substr($invoice->receiver->street, 0, 33),
            'kraj_placnika' => mb_substr($invoice->receiver->city, 0, 33),
            'znesek' => sprintf('%011d', $invoice->total * 100),
            'datum_placila' => '',
            'nujno' => '',
            'koda_namena' => $invoice->pmt_sepa_type,
            'namen_placila' => mb_substr($invoice->title, 0, 42),
            'rok_placila' => $invoice->dat_expire->i18nFormat('dd.MM.yyyy'),
            'iban_prejemnika' => $invoice->issuer->iban,
            'referenca_prejemnika' => mb_substr($invoice->pmt_type . $invoice->pmt_module . $invoice->pmt_ref, 0, 22),
            'ime_prejemnika' => mb_substr($invoice->issuer->title, 0, 33),
            'ulica_prejemnika' => mb_substr($invoice->issuer->street, 0, 33),
            'kraj_prejemnika' => mb_substr($invoice->issuer->city, 0, 33),
        ];

        $checksum = 0;
        foreach ($qrData as $field) {
            $checksum += mb_strlen($field . $qrDelim);
        }
        $qrData['kontrolna_vsota'] = sprintf('%03d', $checksum) . $qrDelim;

        // Combine data to single string
        $qrString = implode($qrDelim, $qrData);

        // Convert data to ISO 8859-2 charset
        $qrString = iconv('UTF-8', 'ISO-8859-2', $qrString);

        $options = new QROptions([
            'version' => 15,
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel' => QRCode::ECC_M,
            'imageBase64' => false,
            'imageTransparent' => false,
        ]);

        // invoke a fresh QRCode instance
        $qrcode = (new QRCode($options))->addEciDesignator(4);
        $qrPng = $qrcode->render($qrString);

        return $qrPng;
    }
}
