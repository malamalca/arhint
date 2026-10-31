<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\Core\Configure;
use Cake\Log\Log;
use DOMXPath;
use Exception;
use Malamalca\FiscalPHP\FiscalSign;
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
     * Send echo request. A successful round trip proves the certificate is accepted by FURS (mutual TLS).
     *
     * @param string $message Message FURS sends back.
     * @return string Echo response.
     * @throws \Exception On transport errors or when FURS does not answer with an echo.
     */
    public function echo(string $message): string
    {
        $raw = $this->send('echoRaw', $message);

        try {
            $doc = FiscalUtils::parseXml($raw);
            $xpath = new DOMXPath($doc);
            $xpath->registerNamespace('soap', FursXml::NS_SOAP);
            $xpath->registerNamespace('fu', FursXml::NS_FU);
            $echo = $xpath->query('/soap:Envelope/soap:Body/fu:EchoResponse');
            if ($echo !== false && $echo->length === 1) {
                return trim((string)$echo->item(0)?->textContent);
            }
        } catch (Exception) {
            // not xml, reported below
        }

        $error = self::parseResponse($raw)['error'];
        Log::error('FURS echo response: ' . mb_substr(self::toUtf8($raw), 0, 2000), 'furs');

        throw new RuntimeException(
            'FURS did not return a valid echo response' . ($error ? ': ' . $error : '') .
            ' [FURS ' . (Configure::read('Documents.furs.production') ? 'production' : 'test') . ': ' . self::url() . ']',
        );
    }

    /**
     * Send request to FURS.
     *
     * @param string $method FiscalSoap method.
     * @param string $signedXml Signed xml (or message for echo).
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

        $ca = self::caBundle();
        try {
            $soap = (new FursSoap())
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
     * Network level check of a FURS service without a certificate: DNS, TCP connect and TLS/HTTP answer.
     *
     * Tells apart "server cannot reach FURS" from "FURS firewall rejects this server" from "FURS is up".
     *
     * @param string $url Service url.
     * @return array{ok: bool, lines: array<string>}
     */
    public static function ping(string $url): array
    {
        $lines = [];
        $ok = false;
        $host = (string)parse_url($url, PHP_URL_HOST);
        $port = (int)(parse_url($url, PHP_URL_PORT) ?: 443);

        $ip = gethostbyname($host);
        if ($ip === $host) {
            return ['ok' => false, 'lines' => ['DNS: cannot resolve ' . $host]];
        }
        $lines[] = 'DNS: ' . $host . ' -> ' . $ip;

        $start = microtime(true);
        $socket = @fsockopen($ip, $port, $errno, $errstr, 5);
        if ($socket === false) {
            return ['ok' => false, 'lines' => array_merge($lines, ['TCP ' . $port . ': failed (' . $errno . ' ' . $errstr . ')'])];
        }
        fclose($socket);
        $lines[] = sprintf('TCP %d: connected in %d ms', $port, (microtime(true) - $start) * 1000);

        $ca = self::caBundle();
        try {
            $conn = curl_init($url);
            curl_setopt_array($conn, [
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_CAINFO => $ca,
            ]);
            $start = microtime(true);
            $body = curl_exec($conn);
            $ms = (int)((microtime(true) - $start) * 1000);
            if ($body === false) {
                // no client certificate is sent, so a handshake failure here still means the server is reachable
                $lines[] = 'TLS/HTTP: ' . curl_error($conn) . ' (' . $ms . ' ms)';
                if (preg_match('/bad record mac|handshake failure|alert/i', curl_error($conn))) {
                    $lines[] = 'Server answered but requires a client certificate: expected for this check.';
                }
                $ok = curl_errno($conn) !== CURLE_SSL_CACERT && curl_errno($conn) !== CURLE_COULDNT_CONNECT;
            } else {
                $text = trim((string)preg_replace('/\s+/', ' ', strip_tags(self::toUtf8((string)$body))));
                $lines[] = 'TLS/HTTP: status ' . curl_getinfo($conn, CURLINFO_HTTP_CODE) . ' in ' . $ms . ' ms';
                if ($text !== '') {
                    $lines[] = 'Response: ' . mb_substr($text, 0, 200);
                }
                $ok = !str_contains($text, 'Request Rejected');
                if (!$ok) {
                    $lines[] = 'FURS firewall rejects requests from this server (IP not allowed).';
                }
            }
        } finally {
            unlink($ca);
        }

        return ['ok' => $ok, 'lines' => $lines];
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
    private static function caBundle(): string
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
