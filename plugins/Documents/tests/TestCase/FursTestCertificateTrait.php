<?php
declare(strict_types=1);

namespace Documents\Test\TestCase;

/**
 * Generates a throw-away self-signed p12 for tests (no FURS certificate is needed for offline tests).
 */
trait FursTestCertificateTrait
{
    protected const P12_PASSWORD = 'test-password';

    /**
     * Create p12 contents. Skips the test when OpenSSL cannot generate keys (missing openssl.cnf on Windows).
     *
     * @param string $unit Organizational unit of the certificate subject.
     * @return string
     */
    protected function createP12(string $unit = '10039953'): string
    {
        $candidates = [
            null,
            getenv('OPENSSL_CONF') ?: '',
            dirname(PHP_BINARY, 2) . '/apache/conf/openssl.cnf',
            'C:/Program Files/Git/usr/ssl/openssl.cnf',
        ];

        $config = [];
        $key = false;
        foreach ($candidates as $candidate) {
            if ($candidate === '' || ($candidate !== null && !is_file($candidate))) {
                continue;
            }
            $config = $candidate === null ? [] : ['config' => $candidate];
            $key = openssl_pkey_new($config + ['private_key_bits' => 2048]);
            if ($key !== false) {
                break;
            }
        }
        if ($key === false) {
            $this->markTestSkipped('OpenSSL cannot generate a test key; set OPENSSL_CONF.');
        }

        $csr = openssl_csr_new(['commonName' => 'FiscalTest', 'organizationalUnitName' => $unit], $key, $config);
        $cert = openssl_csr_sign($csr, null, $key, 1, $config);
        if (!openssl_pkcs12_export($cert, $p12, $key, self::P12_PASSWORD)) {
            $this->markTestSkipped('OpenSSL cannot create a p12 store.');
        }

        return $p12;
    }
}
