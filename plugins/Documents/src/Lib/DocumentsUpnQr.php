<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\ORM\TableRegistry;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class DocumentsUpnQr
{
    /**
     * Generates UpnQr code for specified document
     *
     * @param string $documentId Document id
     * @return string
     */
    public static function generateUpnQr($documentId)
    {
        /** @var \Documents\Model\Table\DocumentsTable $DocumentsTable */
        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        $document = $DocumentsTable->get($documentId, ['contain' => ['Receivers', 'Issuers']]);

        $qrDelim = chr(10);
        $qrData = [
            'vodilni_slog' => 'UPNQR',
            'iban_placnika' => '',
            'polog' => '',
            'dvig' => '',
            'referenca_placnika' => '',
            'ime_placnika' => mb_substr($document->receiver->title, 0, 33),
            'ulica_placnika' => mb_substr($document->receiver->street, 0, 33),
            'kraj_placnika' => mb_substr($document->receiver->city, 0, 33),
            'znesek' => sprintf('%011d', $document->total * 100),
            'datum_placila' => '',
            'nujno' => '',
            'koda_namena' => $document->pmt_sepa_type,
            'namen_placila' => mb_substr($document->title, 0, 42),
            'rok_placila' => $document->dat_expire->i18nFormat('dd.MM.yyyy'),
            'iban_prejemnika' => $document->issuer->iban,
            'referenca_prejemnika' => mb_substr(
                $document->pmt_type . $document->pmt_module . $document->pmt_ref,
                0,
                22
            ),
            'ime_prejemnika' => mb_substr($document->issuer->title, 0, 33),
            'ulica_prejemnika' => mb_substr($document->issuer->street, 0, 33),
            'kraj_prejemnika' => mb_substr($document->issuer->city, 0, 33),
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
