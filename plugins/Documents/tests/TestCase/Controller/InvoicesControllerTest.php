<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\Event\EventList;
use Cake\Event\EventManager;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Documents\Lib\EslogImport;
use Documents\Model\Entity\InvoicesItem;
use Laminas\Diactoros\UploadedFile;
use const UPLOAD_ERR_OK;

/**
 * Documents\Controller\InvoicesController Test Case
 */
class InvoicesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Attachments' => 'app.Attachments',
        'Contacts' => 'plugin.Crm.Contacts',
        'ContactsAddresses' => 'plugin.Crm.ContactsAddresses',
        'ContactsAccounts' => 'plugin.Crm.ContactsAccounts',
        'Invoices' => 'plugin.Documents.Invoices',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'InvoicesItems' => 'plugin.Documents.InvoicesItems',
        'InvoicesTaxes' => 'plugin.Documents.InvoicesTaxes',
        'DocumentsClients' => 'plugin.Documents.DocumentsClients',
        'Vats' => 'plugin.Documents.Vats',
        'Items' => 'plugin.Documents.Items',
        'Expenses' => 'plugin.Expenses.Expenses',
        'Payments' => 'plugin.Expenses.Payments',
        'PaymentsExpenses' => 'plugin.Expenses.PaymentsExpenses',
        'PaymentsAccounts' => 'plugin.Expenses.PaymentsAccounts',
        'Projects' => 'plugin.Projects.Projects',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->configRequest([
            'environment' => [
                'SERVER_NAME' => 'localhost',
            ],
        ]);

        EventManager::instance()->setEventList(new EventList());
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

        $this->get('/documents/invoices/index');
        $this->assertResponseOk();
    }

    /**
     * Test index search method
     *
     * @return void
     */
    public function testIndexSearch()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $this->get('/documents/invoices/index?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88&search=test');
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
        $this->get('/documents/invoices/view/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * * ..\..\vendor\bin\phpunit --filter testAddIssued tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testAddIssued()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');

        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'user_id' => USER_ADMIN,
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'doc_type' => 'IV',

            'title' => 'Totally new document',
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
            'pmt_descript' => 'New document',

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
                'document_id' => null,
                'title' => 'SomeCompany ltd',
            ],
            'receiver' => [
                'id' => null,
                'document_id' => null,
                'title' => 'SomeCompany ltd',
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->setUnlockedFields(['invoices_items', 'buyer', 'receiver']);

        $this->post('/documents/invoices/edit?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices
            ->find()
            ->contain(['InvoicesItems'])
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89'])
            ->orderBy(['created DESC'])->first();

        $this->assertRedirect(['action' => 'view', $invoice->id]);

        $this->assertEquals('Totally new document', $invoice->title);
        $this->assertEquals($counter->counter + 1, $invoice->counter);
        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(100 * 0.7 * 2, $invoice->net_total);
        $this->assertEquals(round((100 * 0.7 * 2) * 1.22, 2), $invoice->total);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $this->assertEquals($counter->counter + 1, $newCounter->counter);

        // test attached clients
        $Contacts = TableRegistry::getTableLocator()->get('Documents.DocumentsClients');
        $buyer = $Contacts
            ->find()
            ->where(['document_id' => $invoice->id, 'DocumentsClients.kind' => 'BY'])
            ->first();

        $this->assertFalse(empty($buyer));
        $this->assertTextEquals('SomeCompany ltd', $buyer->title);

        $seller = $Contacts
            ->find()
            ->where(['document_id' => $invoice->id, 'DocumentsClients.kind' => 'II'])
            ->first();
        $this->assertFalse(empty($seller));
        $this->assertTextEquals('Arhim d.o.o.', $seller->title);
    }

    /**
     * Test add from ArhintScan
     *
     * * ..\..\vendor\bin\phpunit --filter testAddArhintScan tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testAddArhintScan()
    {
        // External program has to send a valid header
        $this->configRequest([
            'headers' => ['Lil-Scan' => 'Valid'],
        ]);
        // Set session data
        $this->login(USER_ADMIN);

        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $counter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');

        $jpgAttachment = new UploadedFile(
            dirname(__FILE__) . DS . 'data' . DS . 'sunset.jpg',
            100963,
            UPLOAD_ERR_OK,
            'sunset.jpg',
            'image/jpg',
        );
        Configure::write('App.uploadFolder', TMP);

        $data = [
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'title' => 'Uploaded Document',
            'dat_issue' => '2020-05-31',
            'attachments' => [
                0 => [
                    'filename' => $jpgAttachment,
                ],
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/invoices/edit', $data);

        $this->assertResponseSuccess();
        $this->assertContentType('application/json');
        $this->assertResponseContains('{"document":{');

        //dd((string)$this->_response->getBody());

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices
            ->find()
            ->contain(['InvoicesItems'])
            ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89'])
            ->orderBy(['created DESC'])->first();

        $this->assertEquals('Uploaded Document', $invoice->title);
        $this->assertEquals($counter->counter + 1, $invoice->counter);

        // test if counter increases
        $newCounter = $counters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $this->assertEquals($counter->counter + 1, $newCounter->counter);

        $this->assertEventFired('Model.beforeSave');

        // test if file exists
        // $this->assertTrue(file_exists(Configure::read('App.uploadFolder') . DS . 'Invoice' . DS . 'sunset.jpg'));
    }

    /**
     * Test edit received document method
     *
     * ..\..\vendor\bin\phpunit --filter testEditReceived tests\TestCase\Controller\DocumentsControllerTest.php
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
            'title' => 'First received document',
            'descript' => 'Usually empty in received documents',
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

        $this->post('/documents/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5');

        $this->assertEquals(120, $invoice->net_total);
        $this->assertEquals(round(120 * 1.22, 2), $invoice->total);
    }

    /**
     * Test edit taxes in received invoice
     *
     * ..\..\vendor\bin\phpunit --filter testEditReceivedReceivedTaxes tests\TestCase\Controller\DocumentsControllerTest.php
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
            'title' => 'First received document',
            'descript' => 'Usually empty in received documents',
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
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
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

        $this->post('/documents/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);

        //var_dump($this->_response);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', contain: ['InvoicesTaxes']);

        $this->assertEquals(2, count($invoice->invoices_taxes));
        $this->assertEquals(110, $invoice->net_total);
        $this->assertEquals(round(100 * 1.22 + 10 * 1.095, 2), $invoice->total);

        //$taxes = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxes');
        //$taxExist = $taxes->exists(['id' => 1]);
        //$this->assertFalse($taxExist);
    }

    /**
     * Test edit items in issued document
     *
     * ..\..\vendor\bin\phpunit --filter testEditIssedDocumentWithItems tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditIssedDocumentWithItems()
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
            'title' => 'First issued document',
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

        $this->post('/documents/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?filter%5Bcounter%5D=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', contain: ['InvoicesItems']);

        $this->assertEquals(2, count($invoice->invoices_items));
        $this->assertEquals(300 + 100 * 0.9 * 2, $invoice->net_total);
        $this->assertEquals(round((300 + 100 * 0.9 * 2) * 1.22, 2), $invoice->total);
    }

    /**
     * Test edit items in issued document with delete
     *
     * ..\..\vendor\bin\phpunit --filter testEditIssedDocumentWithItemsDelete tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditIssedDocumentWithItemsDelete()
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
            'title' => 'First issued document',
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
                    'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
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

        $this->post('/documents/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6?filter%5Bcounter%5D=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', contain: ['InvoicesItems']);

        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(100 * 0.9 * 2, $invoice->net_total);
        $this->assertEquals(round((100 * 0.9 * 2) * 1.22, 2), $invoice->total);

        //$items = TableRegistry::getTableLocator()->get('Documents.DocumentsItems');
        //$deletedItem = $items->exists(['id' => 1]);
        //$this->assertFalse($deletedItem);
    }

    /**
     * Test edit document layouts only
     *
     * ..\..\vendor\bin\phpunit --filter testEditLayouts tests\TestCase\Controller\DocumentsControllerTest.php
     *
     * @return void
     */
    public function testEditLayouts()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', contain: ['InvoicesItems']);

        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(290, $invoice->net_total);
        $this->assertEquals(353.8, $invoice->total);

        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'tpl_header_id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'tpl_body_id' => '',
            'tpl_footer_id' => '',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe6', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe6', contain: ['InvoicesItems']);

        $this->assertEquals(1, count($invoice->invoices_items));
        $this->assertEquals(290, $invoice->net_total);
        $this->assertEquals(353.8, $invoice->total);

        // received
        $data = [
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'tpl_header_id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'tpl_body_id' => '',
            'tpl_footer_id' => '',
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/invoices/edit/d0d59a31-6de7-4eb4-8230-ca09113a7fe5', $data);
        $this->assertRedirect(['action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', contain: ['InvoicesTaxes']);

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

        $this->get('/documents/invoices/delete/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertRedirect(['action' => 'index', '?' => ['counter' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88']]);
    }

    /**
     * Test importEslog GET - shows upload form.
     *
     * @return void
     */
    public function testImportEslogGet()
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/invoices/importEslog?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $this->assertResponseOk();
        $this->assertResponseContains('Import eSlog 2.0 Invoice');
    }

    /**
     * Test importEslog without counter parameter redirects.
     *
     * @return void
     */
    public function testImportEslogWithoutCounter()
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/invoices/importEslog');
        $this->assertRedirect(['action' => 'index']);
    }

    /**
     * Test importEslog POST without file shows error.
     *
     * @return void
     */
    public function testImportEslogPostWithoutFile()
    {
        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/invoices/importEslog?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', []);
        $this->assertResponseSuccess();
        $this->assertResponseContains('Please select an XML file');
    }

    /**
     * Test importEslog POST with valid XML whose client is missing shows the new-client prompt.
     *
     * @return void
     */
    public function testImportEslogPostValidXmlMissingClient()
    {
        $this->login(USER_ADMIN);

        // The test XML buyer tax_no SI98765432 is not present in the Contacts
        // fixture, so the controller should render the "new client" prompt.
        $xmlPath = dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml';
        $this->assertFileExists($xmlPath, 'Test XML file should exist');

        $data = [
            'eslog_file' => new UploadedFile(
                $xmlPath,
                (int)filesize($xmlPath),
                UPLOAD_ERR_OK,
                'test_invoice.xml',
                'application/xml',
            ),
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/invoices/importEslog?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90', $data);

        $this->assertResponseOk();
        $this->assertResponseContains('New Client Required');
        $this->assertResponseContains('SI98765432');
    }

    /**
     * Test importEslog POST with valid XML and an existing client redirects to edit.
     *
     * @return void
     */
    public function testImportEslogPostValidXmlExistingClient()
    {
        $this->login(USER_ADMIN);

        // Insert a contact for the current company matching the XML buyer tax_no
        // so the import skips the new-client prompt and redirects straight to edit.
        $Contacts = TableRegistry::getTableLocator()->get('Crm.Contacts');
        $contact = $Contacts->newEntity([
            'owner_id' => COMPANY_FIRST,
            'kind' => 'C',
            'title' => 'Test Client d.o.o.',
            'tax_no' => 'SI98765432',
            'tax_status' => 1,
        ], ['validate' => false]);
        $Contacts->saveOrFail($contact, ['checkRules' => false]);

        $xmlPath = dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml';

        $data = [
            'eslog_file' => new UploadedFile(
                $xmlPath,
                (int)filesize($xmlPath),
                UPLOAD_ERR_OK,
                'test_invoice.xml',
                'application/xml',
            ),
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/invoices/importEslog?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90', $data);

        $this->assertRedirect([
            'action' => 'edit',
            '?' => ['counter' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90', 'importFromEslog' => '1'],
        ]);
    }

    /**
     * Test that edit() applies imported eSlog data to the document as proper entities.
     *
     * @return void
     */
    public function testEditAppliesImportedEslogData()
    {
        $this->login(USER_ADMIN);

        // Parse the sample invoice and seed the session as importEslog would.
        $xmlPath = dirname(__DIR__) . DS . 'Controller' . DS . 'data' . DS . 'testInvoice_eslog20.xml';
        $xmlContent = file_get_contents($xmlPath);
        $this->assertNotFalse($xmlContent);
        $parsed = (new EslogImport())->parse($xmlContent);
        $this->assertNotNull($parsed);
        $this->session(['ImportEslogData' => $parsed]);

        $this->get('/documents/invoices/edit?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90&importFromEslog=1');
        $this->assertResponseOk();

        /** @var \Documents\Model\Entity\Invoice $document */
        $document = $this->viewVariable('document');
        $this->assertSame('TEST-2025-001', $document->no);
        $this->assertSame('Web development project', $document->title);

        // Items must be marshalled as entities so the edit template can read them as objects.
        $this->assertCount(2, $document->invoices_items);
        $this->assertInstanceOf(
            InvoicesItem::class,
            $document->invoices_items[0],
            'Imported items must be entities, not plain arrays',
        );
        $this->assertSame('Web development services', $document->invoices_items[0]->descript);
        $this->assertEquals(10.0, $document->invoices_items[0]->qty);
        $this->assertEquals(50.0, $document->invoices_items[0]->price);

        // Tax breakdown is grouped by VAT rate (both items are 22%).
        $this->assertCount(1, $document->invoices_taxes);
        $this->assertEquals(22.0, $document->invoices_taxes[0]->vat_percent);
        $this->assertEquals(600.0, $document->invoices_taxes[0]->base);
    }

    /**
     * Test importEslog with invalid XML shows validation errors.
     *
     * @return void
     */
    public function testImportEslogPostInvalidXml()
    {
        $this->login(USER_ADMIN);

        // Create a temp file with invalid XML content
        $tmpFile = TMP . 'invalid_test.xml';
        file_put_contents($tmpFile, '<invalid xml content');

        try {
            $data = [
                'eslog_file' => [
                    'name' => 'invalid.xml',
                    'type' => 'application/xml',
                    'tmp_name' => $tmpFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tmpFile),
                ],
            ];

            $this->enableSecurityToken();
            $this->enableCsrfToken();

            $this->post('/documents/invoices/importEslog?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);

            // Should show validation errors or return to form
            $this->assertResponseSuccess();
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    /**
     * Test importEslog POST with non-XML file shows error.
     *
     * @return void
     */
    public function testImportEslogPostNonXmlFile()
    {
        $this->login(USER_ADMIN);

        // Create a temp file with non-xml extension
        $tmpFile = TMP . 'test_document.pdf';
        file_put_contents($tmpFile, 'fake pdf content');

        try {
            $data = [
                'eslog_file' => [
                    'name' => 'document.pdf',
                    'type' => 'application/pdf',
                    'tmp_name' => $tmpFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tmpFile),
                ],
            ];

            $this->enableSecurityToken();
            $this->enableCsrfToken();

            $this->post('/documents/invoices/importEslog?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);

            $this->assertResponseSuccess();
            $this->assertResponseContains('Please upload a valid XML file');
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    /**
     * Test importPdf GET - shows upload form.
     *
     * @return void
     */
    public function testImportPdfGet()
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/invoices/importPdf?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89');
        $this->assertResponseOk();
        $this->assertResponseContains('Import Invoice from PDF');
    }

    /**
     * Test importPdf without counter parameter redirects.
     *
     * @return void
     */
    public function testImportPdfWithoutCounter()
    {
        $this->login(USER_ADMIN);

        $this->get('/documents/invoices/importPdf');
        $this->assertRedirect(['action' => 'index']);
    }

    /**
     * Test importPdf POST with a non-PDF file shows a validation error (AI is never called).
     *
     * @return void
     */
    public function testImportPdfPostNonPdfFile()
    {
        $this->login(USER_ADMIN);

        $tmpFile = TMP . 'test_document.txt';
        file_put_contents($tmpFile, 'just text, not a pdf');

        try {
            $data = [
                'pdf_file' => [
                    'name' => 'document.txt',
                    'type' => 'text/plain',
                    'tmp_name' => $tmpFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tmpFile),
                ],
            ];

            $this->enableSecurityToken();
            $this->enableCsrfToken();

            $this->post('/documents/invoices/importPdf?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc90', $data);

            $this->assertResponseSuccess();
            $this->assertResponseContains('Please upload a valid PDF file');
        } finally {
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
        }
    }

    /**
     * Test that a PDF stashed by the import flow is attached to the invoice on its first save.
     *
     * @return void
     */
    public function testEditAttachesImportedPdf()
    {
        $this->login(USER_ADMIN);
        Configure::write('App.uploadFolder', TMP);

        // Seed a pending imported PDF, as PdfImportForm would.
        $importDir = TMP . 'import_pdf' . DS;
        if (!is_dir($importDir)) {
            mkdir($importDir, 0775, true);
        }
        $pdfPath = $importDir . 'ctrl_' . uniqid() . '.pdf';
        file_put_contents($pdfPath, '%PDF-1.4 imported invoice');
        $this->session(['ImportPdfAttachment' => ['path' => $pdfPath, 'name' => 'racun.pdf']]);

        $data = [
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'title' => 'Imported via PDF',
            'dat_issue' => '2026-06-28',
        ];
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $dest = TMP . 'Invoice' . DS . 'racun.pdf';
        try {
            $this->post('/documents/invoices/edit?counter=1d53bc5b-de2d-4e85-b13b-81b39a97fc89', $data);
            $this->assertResponseSuccess();

            $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
            $invoice = $Invoices->find()
                ->where(['counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89', 'title' => 'Imported via PDF'])
                ->orderBy(['created DESC'])
                ->first();
            $this->assertNotNull($invoice, 'Invoice should have been saved');

            $Attachments = TableRegistry::getTableLocator()->get('Attachments');
            $attachment = $Attachments->find()
                ->where(['model' => 'Invoice', 'foreign_id' => $invoice->id])
                ->first();
            $this->assertNotNull($attachment, 'The imported PDF should be attached to the invoice');
            $this->assertSame('racun.pdf', $attachment->filename);
            $this->assertSame('application/pdf', $attachment->mimetype);

            // The temp file was moved into the upload folder.
            $this->assertFileExists($dest);
            $this->assertFileDoesNotExist($pdfPath);
        } finally {
            foreach ([$dest, $pdfPath] as $f) {
                if (file_exists($f)) {
                    unlink($f);
                }
            }
        }
    }
}
