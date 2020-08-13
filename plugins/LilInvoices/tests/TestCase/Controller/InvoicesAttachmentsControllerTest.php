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
        'InvoicesCounters' => 'plugin.LilInvoices.InvoicesCounters'
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
        $this->testAdd();

        $InvoicesAttachments = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesAttachments');
        $attachments = $InvoicesAttachments->find()
            ->select()
            ->where(['invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5'])
            ->all();

        $this->assertEquals($attachments->count(), 1);

        $this->get('lil_invoices/invoices-attachments/view/' . $attachments->first()->id);

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
                'name' => 'sunset.jpg',
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
        $invoice = $invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', ['contain' => ['InvoicesAttachments']]);
        $this->assertEquals($invoice->invoices_attachment_count, 1);

        $uploadFolder = Configure::read('LilInvoices.uploadFolder');
        $this->assertTrue(file_exists($uploadFolder . DS . $invoice->invoices_attachments[0]->filename));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->testAdd();
        $InvoicesAttachments = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesAttachments');
        $attachments = $InvoicesAttachments->find()
            ->select()
            ->where(['invoice_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5'])
            ->all();

        $this->assertEquals($attachments->count(), 1);

        $uploadFolder = Configure::read('LilInvoices.uploadFolder');
        $this->assertTrue(file_exists($uploadFolder . DS . $attachments->first()->filename));

        $this->get('lil_invoices/invoices-attachments/delete/' . $attachments->first()->id);
        $this->assertRedirect(['controller' => 'Invoices', 'action' => 'view', $attachments->first()->invoice_id]);

        $this->assertFalse(file_exists($uploadFolder . DS . $attachments->first()->filename));
    }
}
