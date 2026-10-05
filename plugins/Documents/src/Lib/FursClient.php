<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use DOMXPath;
use Exception;
use Malamalca\FiscalPHP\FiscalSign;
use Malamalca\FiscalPHP\FiscalSoap;
use Malamalca\FiscalPHP\FiscalUtils;
use RuntimeException;
use Throwable;

/**
 * Thin wrapper around fiscal-php: signs and sends requests with one user's p12 certificate.
 *
 * The p12 is written to a private temporary file for the lifetime of the object.
 */
class FursClient
{
    private string $p12File;

    /**
     * Constructor
     *
     * @param string $p12 Binary contents of the p12 store.
     * @param string $password Password of the private key.
     */
    public function __construct(string $p12, private string $password)
    {
        $file = tempnam(sys_get_temp_dir(), 'furs');
        if ($file === false) {
            throw new RuntimeException('Cannot create temporary file.');
        }
        chmod($file, 0600);
        file_put_contents($file, $p12);
        $this->p12File = $file;
    }

    /**
     * Destructor removes temporary p12
     */
    public function __destruct()
    {
        if (is_file($this->p12File)) {
            unlink($this->p12File);
        }
    }

    /**
     * Check p12 store can be opened with password.
     *
     * @return array<string, mixed>|null Parsed certificate on success.
     */
    public function certificateInfo(): ?array
    {
        $raw = FiscalUtils::readP12($this->p12File, $this->password);
        if ($raw === false) {
            return null;
        }

        return openssl_x509_parse($raw['cert']) ?: null;
    }

    /**
     * Calculate ZOI.
     *
     * @param string $data String to sign.
     * @return string|null
     */
    public function zoi(string $data): ?string
    {
        $zoi = (new FiscalSign())->setP12($this->p12File)->setPassword($this->password)->zoi($data);

        return $zoi === false ? null : $zoi;
    }

    /**
     * XML-DSig sign.
     *
     * @param string $xml Request xml.
     * @param string $node Node to sign.
     * @return string|null
     */
    public function sign(string $xml, string $node): ?string
    {
        try {
            $signed = (new FiscalSign())->setP12($this->p12File)->setPassword($this->password)->sign($xml, $node);
        } catch (Throwable $e) {
            Log::error('FURS sign error: ' . $e->getMessage(), 'furs');

            return null;
        }

        return $signed === false ? null : $signed;
    }

    /**
     * Send signed invoice request.
     *
     * @param string $signedXml Signed xml.
     * @return string Raw response.
     * @throws \Exception On transport errors.
     */
    public function sendInvoice(string $signedXml): string
    {
        return $this->send('sendInvoiceRaw', $signedXml);
    }

    /**
     * Send signed business premise request.
     *
     * @param string $signedXml Signed xml.
     * @return string Raw response.
     * @throws \Exception On transport errors.
     */
    public function sendPremise(string $signedXml): string
    {
        return $this->send('sendPremiseRaw', $signedXml);
    }

    /**
     * Send request to FURS.
     *
     * @param string $method FiscalSoap method.
     * @param string $signedXml Signed xml.
     * @return string
     */
    private function send(string $method, string $signedXml): string
    {
        $production = (bool)Configure::read('Documents.furs.production');
        if ($production && $this->isTestCertificate()) {
            throw new RuntimeException(
                'The FURS test certificate (DavPotRacTEST) cannot be used with the production service.',
            );
        }

        $ca = $this->caBundle();
        try {
            $soap = (new FiscalSoap())
                ->setUrl(self::url())
                ->setTimeout((int)Configure::read('Documents.furs.timeout', 30) * 1000)
                ->setP12($this->p12File)
                ->setPassword($this->password)
                ->setCert($ca);

            return (string)$soap->{$method}($signedXml);
        } catch (Exception $e) {
            $message = $e->getMessage() . ' [FURS ' . ($production ? 'production' : 'test') . ': ' . self::url() . ']';
            // an unaccepted client certificate shows up as a TLS failure, not as a FURS error message
            if (preg_match('/bad record mac|handshake failure|certificate|alert/i', $e->getMessage())) {
                $message .= ' The FURS server probably rejected the client certificate: use a test certificate with ' .
                    'the test service and a production certificate (with its certificate chain in the p12) with ' .
                    'the production service (Documents.furs.production).';
            }

            throw new RuntimeException($message, 0, $e);
        } finally {
            unlink($ca);
        }
    }

    /**
     * Certificate belongs to the FURS test environment.
     *
     * @return bool
     */
    public function isTestCertificate(): bool
    {
        $info = $this->certificateInfo();

        return in_array('DavPotRacTEST', (array)($info['subject']['OU'] ?? []), true);
    }

    /**
     * Parse FURS response.
     *
     * @param string $xml Response xml.
     * @return array{ok: bool, eor: string|null, error: string|null}
     */
    public static function parseResponse(string $xml): array
    {
        $ret = ['ok' => false, 'eor' => null, 'error' => null];

        try {
            $doc = FiscalUtils::parseXml($xml);
        } catch (Exception $e) {
            // not a SOAP message, e.g. the HTML page of a firewall rejecting the request
            $text = trim((string)preg_replace('/\s+/', ' ', strip_tags(self::toUtf8($xml))));
            $ret['error'] = 'Invalid response' . ($text !== '' ? ': ' . mb_substr($text, 0, 400) : '');

            return $ret;
        }

        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('soap', FursXml::NS_SOAP);
        $xpath->registerNamespace('fu', FursXml::NS_FU);

        $errors = $xpath->query('//fu:Error/fu:ErrorMessage | //soap:Fault/faultstring');
        if ($errors !== false && $errors->length > 0) {
            $codes = $xpath->query('//fu:Error/fu:ErrorCode');
            $ret['error'] = trim(
                ($codes !== false && $codes->length ? $codes->item(0)?->textContent . ': ' : '') .
                $errors->item(0)?->textContent,
            );

            return $ret;
        }

        $eor = $xpath->query('/soap:Envelope/soap:Body/fu:InvoiceResponse/fu:UniqueInvoiceID');
        if ($eor !== false && $eor->length === 1) {
            $ret['ok'] = true;
            $ret['eor'] = trim((string)$eor->item(0)?->textContent);

            return $ret;
        }

        $premise = $xpath->query('/soap:Envelope/soap:Body/fu:BusinessPremiseResponse');
        if ($premise !== false && $premise->length === 1) {
            $ret['ok'] = true;

            return $ret;
        }

        $ret['error'] = 'Unexpected response';

        return $ret;
    }

    /**
     * Convert a response to valid UTF-8. FURS error pages are not UTF-8 (ISO-8859-2).
     *
     * @param string $text Response text.
     * @return string
     */
    public static function toUtf8(string $text): string
    {
        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        return mb_convert_encoding($text, 'UTF-8', 'ISO-8859-2');
    }

    /**
     * FURS service url.
     *
     * @return string
     */
    public static function url(): string
    {
        $key = Configure::read('Documents.furs.production') ? 'production' : 'test';

        return (string)Configure::read('Documents.furs.urls.' . $key);
    }

    /**
     * Creates temporary PEM bundle of FURS CA certificates; caller unlinks it.
     *
     * @return string
     */
    private function caBundle(): string
    {
        $bundle = '';
        foreach ((array)Configure::read('Documents.furs.caFiles') as $caFile) {
            $pem = FiscalUtils::cerToPem($caFile);
            if ($pem === false) {
                throw new RuntimeException('Invalid FURS CA certificate: ' . $caFile);
            }
            try {
                $bundle .= file_get_contents($pem) . "\n";
            } finally {
                unlink($pem);
            }
        }

        $file = tempnam(sys_get_temp_dir(), 'fursca');
        if ($file === false) {
            throw new RuntimeException('Cannot create temporary file.');
        }
        file_put_contents($file, $bundle);

        return $file;
    }
}
