<?php
declare(strict_types=1);

namespace LilInvoices\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * LilInvoices\Controller\InvoicesController Test Case
 */
class InvoicesControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Contacts' => 'plugin.LilCrm.Contacts',
        'ContactsAddresses' => 'plugin.LilCrm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.LilCrm.ContactsAccounts',
        'Invoices' => 'plugin.LilInvoices.Invoices',
        'InvoicesCounters' => 'plugin.LilInvoices.InvoicesCounters',
        'InvoicesAttachments' => 'plugin.LilInvoices.InvoicesAttachments',
        'InvoicesLinks' => 'plugin.LilInvoices.InvoicesLinks',
        'InvoicesItems' => 'plugin.LilInvoices.InvoicesItems',
        'InvoicesTaxes' => 'plugin.LilInvoices.InvoicesTaxes',
        'InvoicesClients' => 'plugin.LilInvoices.InvoicesClients',
        'Vats' => 'plugin.LilInvoices.Vats',
        'Items' => 'plugin.LilInvoices.Items',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ]
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
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('lil_invoices/invoices/index');
        $this->assertResponseOk();
    }

    /**
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        // Set session data
        $this->login(USER_ADMIN);
        $this->get('lil_invoices/invoices/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * * ..\..\vendor\bin\phpunit --filter testAddIssued tests\TestCase\Controller\InvoicesControllerTest.php
     *
     * @return void
     */
    public function testAddIssued()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $counters = TableRegistry::get('LilInvoices.InvoicesCounters');
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');

        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'user_id' => USER_ADMIN,
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'doc_type' => 'IV',

            'title' => 'Totally new invoice',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,

            'pmt_kind' => 0,
            'pmt_sepa_type' => 'OTHR',
            'pmt_type' => 'SI',
            'pmt_module' => '00',
            'pmt_ref' => '02',
            'pmt_descript' => 'New invoice',

            'location' => 'Ljubljana',

            'invoices_items' => [
                // and new item
                [
                    'id' => null,
                    'invoice_id' => null,
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_descript' => '22 %',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13 SE',
                    'qty' => 2,
                    'unit' => 'pcs',
                    'price' => 100,
                    'discount' => 30,
                ],
            ],
            'buyer' => [
                'id' => null,
                'invoice_id' => null,
                'title' => 'SomeCompany ltd',
            ],
            'receiver' => [
                'id' => null,
                'invoice_id' => null,
                'title' => 'SomeCompany ltd',
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['invoices_items', 'buyer', 'receiver']);

        $this->post('lil_invoices/invoices/add?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);

        $Invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $Invoices
            ->find()
            ->contain(['InvoicesItems'])
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89'])
            ->order(['created DESC'])->first();

        $this->assertRedirect(['action' => 'view', $invoice->id]);

        $this->assertEquals('Totally new invoice', $invoice->title);
        $this->assertEquals($counter->counter + 1, $invoice->counter);
        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(100 * 0.7 * 2, $invoice->net_total);
        $this->assertEquals(round((100 * 0.7 * 2) * 1.22, 2), $invoice->total);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $this->assertEquals($counter->counter + 1, $newCounter->counter);

        // test attached clients
        $Contacts = TableRegistry::get('LilInvoices.InvoicesClients');
        $buyer = $Contacts
            ->find()
            ->where(['invoice_id' => $invoice->id, 'InvoicesClients.kind' => 'BY'])
            ->first();

        $this->assertFalse(empty($buyer));
        $this->assertTextEquals('SomeCompany ltd', $buyer->title);

        $seller = $Contacts
            ->find()
            ->where(['invoice_id' => $invoice->id, 'InvoicesClients.kind' => 'II'])
            ->first();
        $this->assertFalse(empty($seller));
        $this->assertTextEquals('Arhim d.o.o.', $seller->title);
    }

    /**
     * Test edit received invoice method
     *
     * ..\..\vendor\bin\phpunit --filter testEditReceived tests\TestCase\Controller\InvoicesControllerTest.php
     *
     * @return void
     */
    public function testEditReceived()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'kind' => 'IV',
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'First received invoice',
            'descript' => 'Usually empty in received invoices',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'total' => 146.4,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI12',
            'pmt_ref' => '1234',
            'invoices_taxes' => [
                [
                    'id' => 1,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'base' => 120,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['invoices_taxes']);

        $this->post('lil_invoices/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5');

        $this->assertEquals(120, $invoice->net_total);
        $this->assertEquals(round(120 * 1.22, 2), $invoice->total);
    }

    /**
     * Test edit taxes in received invoice
     *
     * ..\..\vendor\bin\phpunit --filter testEditReceivedReceivedTaxes tests\TestCase\Controller\InvoicesControllerTest.php
     *
     * @return void
     */
    public function testEdit2ReceivedInvoicesTaxes()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'owner_id' => COMPANY_FIRST,
            //'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'kind' => 'IV',
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'First received invoice',
            'descript' => 'Usually empty in received invoices',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'total' => 132.95,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI12',
            'pmt_ref' => '1234',
            'invoices_taxes' => [
                /*
                // delete this item
                [
                    'id' => 1,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'base' => 120,
                ],*/
                // and add two new items
                [
                    'id' => null,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'base' => 100,
                ],
                [
                    'id' => null,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                    'vat_id' => 'e0ef11f0-0d75-4731-a147-9efaf4462e93',
                    'vat_title' => 'DDV 9.5%',
                    'vat_percent' => 9.5,
                    'base' => 10,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['invoices_taxes']);

        $this->post('lil_invoices/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);

        //var_dump($this->_response);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', ['contain' => ['InvoicesTaxes']]);

        $this->assertEquals(2, count($invoice->invoices_taxes));
        $this->assertEquals(110, $invoice->net_total);
        $this->assertEquals(round(100 * 1.22 + 10 * 1.095, 2), $invoice->total);

        $taxes = TableRegistry::get('LilInvoices.InvoicesTaxes');
        $taxExist = $taxes->exists(['id' => 1]);
        $this->assertFalse($taxExist);
    }

    /**
     * Test edit items in issued invoice
     *
     * ..\..\vendor\bin\phpunit --filter testEditIssedInvoiceWithItems tests\TestCase\Controller\InvoicesControllerTest.php
     *
     * @return void
     */
    public function testEditIssedInvoiceWithItems()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'counter' => 1,
            'no' => 'ISU-01',
            'title' => 'First issued invoice',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI00',
            'pmt_ref' => '01',
            'invoices_items' => [
                // edit items price
                [
                    'id' => 1,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13',
                    'qty' => 1,
                    'unit' => 'pcs',
                    'price' => 300,
                    'discount' => 0,
                ],
                // and new item
                [
                    'id' => null,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13 SE',
                    'qty' => 2,
                    'unit' => 'pcs',
                    'price' => 100,
                    'discount' => 10,
                ],
            ],
        ];

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->setUnlockedFields(['invoices_items']);

        $this->post('lil_invoices/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?filter%5Bcounter%5D=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['InvoicesItems']]);

        $this->assertEquals(2, count($invoice->invoices_items));
        $this->assertEquals(300 + 100 * 0.9 * 2, $invoice->net_total);
        $this->assertEquals(round((300 + 100 * 0.9 * 2) * 1.22, 2), $invoice->total);
    }

    /**
     * Test edit items in issued invoice with delete
     *
     * ..\..\vendor\bin\phpunit --filter testEditIssedInvoiceWithItemsDelete tests\TestCase\Controller\InvoicesControllerTest.php
     *
     * @return void
     */
    public function testEditIssedInvoiceWithItemsDelete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'counter' => 1,
            'no' => 'ISU-01',
            'title' => 'First issued invoice',
            'descript' => 'Payment details etc',
            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'pmt_type' => 'OTHR',
            'pmt_module' => 'SI00',
            'pmt_ref' => '01',
            'invoices_items' => [
                // delete this item
                /*[
                    'id' => 1,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'descript' => 'Hrup\' 13',
                    'qty' => 1,
                    'unit' => 'pcs',
                    'price' => 300,
                    'discount' => 0,
                ],*/
                // and new item
                [
                    'id' => null,
                    'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                    'item_id' => null,
                    'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
                    'vat_title' => 'DDV 22%',
                    'vat_percent' => 22,
                    'descript' => 'Hrup\' 13 SE',
                    'qty' => 2,
                    'unit' => 'pcs',
                    'price' => 100,
                    'discount' => 10,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['invoices_items']);

        $this->post('lil_invoices/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?filter%5Bcounter%5D=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['InvoicesItems']]);

        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(100 * 0.9 * 2, $invoice->net_total);
        $this->assertEquals(round((100 * 0.9 * 2) * 1.22, 2), $invoice->total);

        $items = TableRegistry::get('LilInvoices.InvoicesItems');
        $deletedItem = $items->exists(['id' => 1]);
        $this->assertFalse($deletedItem);
    }

    /**
     * Test edit invoice layouts only
     *
     * ..\..\vendor\bin\phpunit --filter testEditLayouts tests\TestCase\Controller\InvoicesControllerTest.php
     *
     * @return void
     */
    public function testEditLayouts()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'tpl_header_id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'tpl_body_id' => '',
            'tpl_footer_id' => '',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('lil_invoices/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', ['contain' => ['InvoicesItems']]);

        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(1, $invoice->invoices_items[0]->id);

        // received
        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'tpl_header_id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'tpl_body_id' => '',
            'tpl_footer_id' => '',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('lil_invoices/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', ['contain' => ['InvoicesTaxes']]);

        $this->assertEquals(1, count($invoice->invoices_taxes));
        $this->assertEquals(1, $invoice->invoices_taxes[0]->id);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('lil_invoices/invoices/delete/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertRedirect(['action' => 'index', '?' => ['counter' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88']]);
    }
}