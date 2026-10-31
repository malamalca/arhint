<?php
declare(strict_types=1);

namespace Documents\Lib;

use Malamalca\FiscalPHP\FiscalSoap;

/**
 * FiscalSoap that can return the raw echo response. FiscalSoap::sendEcho() swallows FURS error messages.
 */
class FursSoap extends FiscalSoap
{
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
}
