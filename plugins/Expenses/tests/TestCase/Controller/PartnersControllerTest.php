<?php
declare(strict_types=1);

namespace Expenses\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Expenses\Controller\PartnersController Test Case
 */
class PartnersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Expenses.Partners',
        'plugin.Crm.Contacts',
    ];

    /**
     * Login as a given user.
     *
     * @param string $userId User id.
     * @return void
     */
    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method.
     *
     * @return void
     */
    public function testIndex(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/partners/index');
        $this->assertResponseOk();
    }

    /**
     * Test index method with search filter.
     *
     * @return void
     */
    public function testIndexWithSearch(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/partners/index?search=Arhim');
        $this->assertResponseOk();
    }

    /**
     * Test index method with role filter.
     *
     * @return void
     */
    public function testIndexWithRole(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/partners/index?role=buyer');
        $this->assertResponseOk();
    }

    /**
     * Test add (GET) renders form.
     *
     * @return void
     */
    public function testAddGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/partners/edit?contact_id=8155426d-2302-4fa5-97de-e33cefb9d704');
        $this->assertResponseOk();
    }

    /**
     * Test add (POST) creates a new partner.
     *
     * @return void
     */
    public function testAddPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'contact_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'role' => 'buyer',
            'date_start' => '2026-01-01',
            'date_end' => '',
        ];

        $this->post('/expenses/partners/edit', $data);
        $this->assertRedirectContains('/expenses/partners');
    }

    /**
     * Test edit (GET) renders form with existing record.
     *
     * @return void
     */
    public function testEditGet(): void
    {
        $this->login(USER_ADMIN);

        $this->get('/expenses/partners/edit/' . PARTNER_1);
        $this->assertResponseOk();
    }

    /**
     * Test edit (POST) updates an existing partner.
     *
     * @return void
     */
    public function testEditPost(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'contact_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'role' => 'seller',
            'date_start' => '2020-06-01',
            'date_end' => '',
        ];

        $this->post('/expenses/partners/edit/' . PARTNER_1, $data);
        $this->assertRedirectContains('/expenses/partners');
    }

    /**
     * Test delete action removes a partner.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->delete('/expenses/partners/delete/' . PARTNER_1);
        $this->assertRedirectContains('/crm/contacts/view');

        /** @var \Expenses\Model\Table\PartnersTable $Partners */
        $Partners = TableRegistry::getTableLocator()->get('Expenses.Partners');
        $this->assertSame(1, $Partners->find()->count());
    }
}
