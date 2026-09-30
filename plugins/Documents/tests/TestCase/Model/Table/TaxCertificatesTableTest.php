<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Documents\Lib\FursClient;
use Documents\Model\Table\TaxCertificatesTable;
use Documents\Test\TestCase\FursTestCertificateTrait;

class TaxCertificatesTableTest extends TestCase
{
    use FursTestCertificateTrait;

    public array $fixtures = [
        'plugin.Documents.TaxCertificates',
    ];

    private const USER = '048acacf-d87c-4088-a3a7-4bab30f6a040';

    private TaxCertificatesTable $Certificates;

    public function setUp(): void
    {
        parent::setUp();

        $this->Certificates = TableRegistry::getTableLocator()->get('Documents.TaxCertificates');
    }

    public function testStoreEncryptsPasswordAndRoundTrips(): void
    {
        $cert = $this->Certificates->store(self::USER, $this->createP12(), self::P12_PASSWORD, 'SI10039953');

        $this->assertNotNull($cert);
        $this->assertSame('10039953', $cert->tax_no);
        $this->assertNotNull($cert->valid_to);

        $row = $this->Certificates->find()->where(['user_id' => self::USER])->disableHydration()->first();
        $this->assertNotSame(self::P12_PASSWORD, $row['password']);
        $this->assertStringNotContainsString(self::P12_PASSWORD, (string)$row['password']);
        $this->assertSame(self::P12_PASSWORD, TaxCertificatesTable::decryptPassword($row['password']));

        $client = $this->Certificates->clientForUser(self::USER);
        $this->assertInstanceOf(FursClient::class, $client);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', (string)$client->zoi('data'));
    }

    public function testStoreRejectsWrongPassword(): void
    {
        $this->assertNull($this->Certificates->store(self::USER, $this->createP12(), 'wrong', null));
        $this->assertSame(0, $this->Certificates->find()->count());
        $this->assertNull($this->Certificates->clientForUser(self::USER));
    }

    public function testStoreWithoutFileKeepsStoredCertificate(): void
    {
        $this->Certificates->store(self::USER, $this->createP12(), self::P12_PASSWORD, null);

        $this->assertNotNull($this->Certificates->store(self::USER, null, self::P12_PASSWORD, '10039953'));
        $this->assertNull($this->Certificates->store(self::USER, null, 'wrong', null));
        $this->assertSame(1, $this->Certificates->find()->count());
        $this->assertSame('10039953', $this->Certificates->findForUser(self::USER)->tax_no);
    }

    public function testNoCertificateForUnknownUser(): void
    {
        $this->assertNull($this->Certificates->clientForUser('nobody'));
    }
}
