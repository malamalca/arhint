<?php
declare(strict_types=1);

namespace LilInvoices\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * LilInvoices\Controller\InvoicesCountersController Test Case
 */
class InvoicesCountersControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'InvoicesCounters' => 'plugin.LilInvoices.InvoicesCounters',
        'InvoicesTemplates' => 'plugin.LilInvoices.InvoicesTemplates',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ],
        ]);
    }

    private function login($userId)
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    /**
     * Test index method
     *
     * @return void
     */
    public function testIndex()
    {
        $this->get('lil_invoices/invoices-counters');
        $this->assertRedirect();

        $this->login(USER_ADMIN);
        $this->get('lil_invoices/invoices-counters');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'received',
            'doc_type' => null,
            'expense' => 0,
            'counter' => 0,
            'title' => 'Test Counter',
            'mask' => null,
            'layout' => null,
            'layout_title' => 'Test Counter [[no]]',
            'template_descript' => null,
            'header' => null,
            'footer' => null,
            'active' => 1,
        ];

        $this->get('lil_invoices/invoices-counters/edit', $data);
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('lil_invoices/invoices-counters/edit', $data);
        $this->assertRedirect(['controller' => 'InvoicesCounters', 'action' => 'index']);

        $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');
        $invoices = $InvoicesCounters->find()->select()->where(['title' => 'Test Counter'])->all();
        $this->assertEquals(1, $invoices->count());
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        $data = [
            'id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'kind' => 'received',
            'doc_type' => null,
            'expense' => 1,
            'counter' => 1,
            'title' => 'Edited Title From TestSuite',
            'mask' => null,
            'layout' => null,
            'layout_title' => 'Received [[no]]',
            'template_descript' => null,
            'active' => 1,
        ];

        $this->get('lil_invoices/invoices-counters/edit/1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('lil_invoices/invoices-counters/edit/1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect(['controller' => 'InvoicesCounters', 'action' => 'index']);

        $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');
        $invoice = $InvoicesCounters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc88');
        $this->assertEquals('Edited Title From TestSuite', $invoice->title);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->login(USER_ADMIN);

        $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');
        $countBefore = $InvoicesCounters->find()
            ->where(['owner_id' => COMPANY_FIRST])
            ->count();

        $this->get('lil_invoices/invoices-counters/delete/1d53bc5b-de2d-4e85-b13b-81b39a97fc88');
        $this->assertRedirect(['controller' => 'InvoicesCounters', 'action' => 'index']);

        $countAfter = $InvoicesCounters->find()
            ->where(['owner_id' => COMPANY_FIRST])
            ->count();

        $this->assertEquals($countBefore - 1, $countAfter);
    }
}
