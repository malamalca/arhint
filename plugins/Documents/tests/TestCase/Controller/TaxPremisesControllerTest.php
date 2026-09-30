<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Documents\Controller\TaxPremisesController Test Case
 */
class TaxPremisesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private const OTHER_COMPANY = '7d5c7465-9487-4203-ae67-ddb191c42816';

    public array $fixtures = [
        'Users' => 'app.Users',
        'plugin.Documents.DocumentsCounters',
        'plugin.Documents.TaxPremises',
        'plugin.Documents.TaxCertificates',
    ];

    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    private function createPremise(string $ownerId = COMPANY_FIRST, array $data = []): string
    {
        $table = TableRegistry::getTableLocator()->get('Documents.TaxPremises');
        $premise = $table->newEntity($data + [
            'no' => 'PP1',
            'title' => 'Shop',
            'kind' => 'MO',
            'mo_type' => 'B',
            'validity_date' => '2026-10-01',
            'sw_taxno' => '10039953',
        ]);
        $premise->owner_id = $ownerId;
        $table->saveOrFail($premise);

        return $premise->id;
    }

    public function testIndex(): void
    {
        $this->login(USER_ADMIN);
        $this->createPremise();

        $this->get('/documents/tax-premises/index');

        $this->assertResponseOk();
        $this->assertResponseContains('PP1');
    }

    public function testIndexShowsOnlyOwnPremises(): void
    {
        $this->login(USER_ADMIN);
        $this->createPremise(self::OTHER_COMPANY, ['no' => 'FOREIGN']);

        $this->get('/documents/tax-premises/index');

        $this->assertResponseOk();
        $this->assertResponseNotContains('FOREIGN');
    }

    public function testAdd(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->get('/documents/tax-premises/edit');
        $this->assertResponseOk();

        $this->post('/documents/tax-premises/edit', [
            'no' => 'PP2',
            'title' => 'Office',
            'kind' => 'RL',
            'casadral_number' => '365',
            'building_number' => '12',
            'building_section_number' => '3',
            'street' => 'Slakova ulica',
            'house_number' => '36',
            'community' => 'Trebnje',
            'city' => 'Trebnje',
            'postal_code' => '8210',
            'validity_date' => '2026-10-01',
            'sw_taxno' => '10039953',
        ]);

        $this->assertRedirect(['action' => 'index']);
        $premise = TableRegistry::getTableLocator()->get('Documents.TaxPremises')->find()->where(['no' => 'PP2'])->first();
        $this->assertSame(COMPANY_FIRST, $premise->owner_id);
        $this->assertFalse($premise->active);
    }

    public function testAddRealEstateRequiresAddress(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/tax-premises/edit', [
            'no' => 'PP3',
            'kind' => 'RL',
            'validity_date' => '2026-10-01',
            'sw_taxno' => '10039953',
        ]);

        $this->assertResponseOk();
        $this->assertSame(0, TableRegistry::getTableLocator()->get('Documents.TaxPremises')->find()->count());
    }

    public function testEditCannotChangeActiveFlag(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $id = $this->createPremise();

        $this->post('/documents/tax-premises/edit/' . $id, ['title' => 'Renamed', 'active' => 1]);

        $this->assertRedirect(['action' => 'index']);
        $premise = TableRegistry::getTableLocator()->get('Documents.TaxPremises')->get($id);
        $this->assertSame('Renamed', $premise->title);
        $this->assertFalse($premise->active);
    }

    public function testEditForeignPremiseIsForbidden(): void
    {
        $this->login(USER_ADMIN);
        $id = $this->createPremise(self::OTHER_COMPANY);

        $this->get('/documents/tax-premises/edit/' . $id);

        $this->assertResponseCode(403);
    }

    public function testDelete(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $id = $this->createPremise();

        $this->post('/documents/tax-premises/delete/' . $id);

        $this->assertRedirect(['action' => 'index']);
        $this->assertFalse(TableRegistry::getTableLocator()->get('Documents.TaxPremises')->exists(['id' => $id]));
    }

    public function testDeleteUsedPremiseIsRefused(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $id = $this->createPremise();
        TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->updateAll(['tax_premise_id' => $id], ['id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89']);

        $this->post('/documents/tax-premises/delete/' . $id);

        $this->assertRedirect(['action' => 'index']);
        $this->assertTrue(TableRegistry::getTableLocator()->get('Documents.TaxPremises')->exists(['id' => $id]));
    }

    public function testRegisterWithoutCertificateRedirectsToCertificate(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $id = $this->createPremise();

        $this->post('/documents/tax-premises/register/' . $id);

        $this->assertRedirect(['action' => 'certificate']);
    }

    public function testCertificatePage(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/tax-premises/certificate');

        $this->assertResponseOk();
    }

    public function testCounterFormHasTaxFields(): void
    {
        $this->login(USER_ADMIN);
        $this->createPremise();

        $this->get('/documents/documents-counters/edit');

        $this->assertResponseOk();
        $this->assertResponseContains('tax-confirmation');
        $this->assertResponseContains('PP1 - Shop');
    }

    public function testCounterRequiresPremiseWhenConfirmationEnabled(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents-counters/edit', [
            'kind' => 'Invoices',
            'direction' => 'issued',
            'title' => 'Tax counter',
            'counter' => 0,
            'mask' => '[[no]]',
            'active' => 1,
            'tax_confirmation' => 1,
            'tax_premise_id' => '',
            'device_no' => '',
        ]);

        $this->assertResponseOk();
        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $this->assertFalse($counters->exists(['title' => 'Tax counter']));
    }
}
