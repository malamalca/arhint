<?php
declare(strict_types=1);

namespace Documents\Lib;

use Exception;
use Malamalca\FiscalPHP\FiscalSoap;
use Malamalca\FiscalPHP\FiscalUtils;

/**
 * FiscalSoap that reports the body of failed (non 2xx) responses and can return the raw echo response.
 *
 * FURS answers some rejected requests with HTTP 500 and the reason in the body, which FiscalSoap drops.
 */
class FursSoap extends FiscalSoap
{
    private string $caFile = '';
    private string $p12File = '';
    private string $keyPassword = '';
    private string $serviceUrl = '';
    private int $timeout = 3000;

    /**
     * @inheritDoc
     */
    public function setUrl($url)
    {
        $this->serviceUrl = (string)$url;

        return parent::setUrl($url);
    }

    /**
     * @inheritDoc
     */
    public function setTimeout($milliseconds)
    {
        $this->timeout = max(1, (int)$milliseconds);

        return parent::setTimeout($milliseconds);
    }

    /**
     * @inheritDoc
     */
    public function setCert($fileName)
    {
        $this->caFile = (string)$fileName;

        return parent::setCert($fileName);
    }

    /**
     * @inheritDoc
     */
    public function setP12($fileName)
    {
        $this->p12File = (string)$fileName;

        return parent::setP12($fileName);
    }

    /**
     * @inheritDoc
     */
    public function setPassword($password)
    {
        $this->keyPassword = (string)$password;

        return parent::setPassword($password);
    }

    /**
     * Send echo request.
     *
     * @param string $message Message FURS sends back.
     * @return string Raw response.
     */
    public function echoRaw(string $message): string
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' .
            '<soapenv:Envelope xmlns:soapenv="' . FursXml::NS_SOAP . '" xmlns:fu="' . FursXml::NS_FU . '">' .
            '<soapenv:Body><fu:EchoRequest>' . htmlspecialchars($message, ENT_XML1 | ENT_QUOTES, 'UTF-8') .
            '</fu:EchoRequest></soapenv:Body></soapenv:Envelope>';

        return (string)$this->doRequest('echo', $xml);
    }

    /**
     * @inheritDoc
     */
    protected function doRequest($action, $xml)
    {
        $privateKey = false;
        $ca = false;
        $conn = null;
        try {
            $privateKey = FiscalUtils::p12ToPem($this->p12File, $this->keyPassword);
            if ($privateKey === false) {
                throw new Exception('ERROR: Cannot parse P12');
            }
            $ca = FiscalUtils::cerToPem($this->caFile);
            if ($ca === false) {
                throw new Exception('ERROR: Cannot parse CA Info');
            }

            $conn = curl_init();
            curl_setopt_array($conn, [
                CURLOPT_URL => $this->serviceUrl,
                CURLOPT_CONNECTTIMEOUT_MS => 3000,
                CURLOPT_TIMEOUT_MS => $this->timeout,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: text/xml; charset=utf-8',
                    'Cache-Control: no-cache',
                    'Pragma: no-cache',
                    'SOAPAction: /' . $action,
                ],
                CURLOPT_POSTFIELDS => $xml,
                CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSLCERT => $privateKey,
                CURLOPT_SSLCERTPASSWD => $this->keyPassword,
                CURLOPT_CAINFO => $ca,
            ]);

            $response = curl_exec($conn);
            if ($response === false) {
                throw new Exception('CODECURL: ' . curl_error($conn));
            }

            $status = curl_getinfo($conn, CURLINFO_HTTP_CODE);
            if ($status < 200 || $status >= 300) {
                $body = trim((string)preg_replace('/\s+/', ' ', FursClient::toUtf8((string)$response)));

                throw new Exception('HTTP error: ' . $status . ($body !== '' ? ': ' . mb_substr($body, 0, 800) : ''));
            }
            if ($response === '') {
                throw new Exception('Empty response from fiscal service.');
            }

            return $response;
        } finally {
            unset($conn);
            if ($privateKey !== false) {
                unlink($privateKey);
            }
            if ($ca !== false) {
                unlink($ca);
            }
        }
    }
}
