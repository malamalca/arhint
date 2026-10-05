<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Lib;

use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Documents\Lib\FursClient;
use Documents\Test\TestCase\FursTestCertificateTrait;
use RuntimeException;

class FursClientTest extends TestCase
{
    use FursTestCertificateTrait;

    public function tearDown(): void
    {
        Configure::write('Documents.furs.production', false);

        parent::tearDown();
    }

    public function testDetectsTestCertificate(): void
    {
        $this->assertTrue((new FursClient($this->createP12('DavPotRacTEST'), self::P12_PASSWORD))->isTestCertificate());
        $this->assertFalse((new FursClient($this->createP12('10039953'), self::P12_PASSWORD))->isTestCertificate());
    }

    public function testTestCertificateIsRefusedOnProduction(): void
    {
        Configure::write('Documents.furs.production', true);
        $client = new FursClient($this->createP12('DavPotRacTEST'), self::P12_PASSWORD);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cannot be used with the production service');

        $client->sendPremise('<request/>');
    }
}
