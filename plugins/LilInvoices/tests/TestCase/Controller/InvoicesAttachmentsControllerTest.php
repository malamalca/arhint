<?php
declare(strict_types=1);

namespace LilInvoices\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * LilInvoices\Controller\InvoicesAttachmentsController Test Case
 */
class InvoicesAttachmentsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Invoices' => 'plugin.LilInvoices.Invoices',
        'InvoicesAttachments' => 'plugin.LilInvoices.InvoicesAttachments',
        'InvoicesCounters' => 'plugin.LilInvoices.InvoicesCounters',
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
     * Test view method
     *
     * @return void
     */
    public function testView()
    {
        $this->login(USER_ADMIN);

        $this->get('lil_invoices/invoices-attachments/view/aef61652-7416-43b4-9bb4-198f5706ed74');

        $this->assertResponseOk();
    }

    /**
     * Test add method
     * vendor\bin\phpunit --filter testAdd plugins\LilInvoices\tests\TestCase\Controller\InvoicesAttachmentsControllerTest.php
     *
     * @return void
     */
    public function testAdd()
    {
        $this->login(USER_ADMIN);

        $data = [
            'id' => null,
            'invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'filename' => [
                'name' => 'sunset_uploaded.jpg',
                'type' => 'image/jpg',
                'size' => 100963,
                'tmp_name' => dirname(__FILE__) . DS . 'data' . DS . 'sunset.jpg',
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('lil_invoices/invoices-attachments/add/d0d59a31-6de7-4eb4-8230-ca09113a7fe5', $data);

        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $invoices = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertEquals($invoice->invoices_attachment_count, 2);

        $invoicesAttachmentsTable = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesAttachments');
        $a = $invoicesAttachmentsTable->find()
            ->select()
            ->where(['original' => 'sunset_uploaded.jpg'])
            ->first();

        $uploadFolder = Configure::read('LilInvoices.uploadFolder');
        $this->assertTrue(file_exists($uploadFolder . DS . $a->filename));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->login(USER_ADMIN);

        $this->get('lil_invoices/invoices-attachments/delete/aef61652-7416-43b4-9bb4-198f5706ed74');
        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);
    }
}
