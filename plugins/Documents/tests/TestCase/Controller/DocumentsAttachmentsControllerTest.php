<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use const UPLOAD_ERR_OK;

/**
 * Documents\Controller\DocumentsAttachmentsController Test Case
 */
class DocumentsAttachmentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'Invoices' => 'plugin.Documents.Invoices',
        'DocumentsAttachments' => 'plugin.Documents.DocumentsAttachments',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
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

        $this->get('/documents/documents-attachments/view/aef61652-7416-43b4-9bb4-198f5706ed74');

        $this->assertResponseOk();
    }

    /**
     * Test add method
     * vendor\bin\phpunit --filter testAdd plugins\Documents\tests\TestCase\Controller\DocumentsAttachmentsControllerTest.php
     *
     * @return void
     */
    public function testAdd(): void
    {
        $this->login(USER_ADMIN);

        $jpgAttachment = new UploadedFile(
            dirname(__FILE__) . DS . 'data' . DS . 'sunset.jpg',
            100963,
            UPLOAD_ERR_OK,
            'sunset_uploaded.jpg',
            'image/jpg'
        );

        $data = [
            'id' => null,
            'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'filename' => $jpgAttachment,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents-attachments/add/Invoice/d0d59a31-6de7-4eb4-8230-ca09113a7fe5', $data);

        //$this->assertRedirect(['controller' => 'Invoices', 'action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);
        $this->assertRedirect(['controller' => 'DocumentsAttachments', 'action' => 'index']);

        $Invoices = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $invoice = $Invoices->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertEquals($invoice->attachments_count, 2);

        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');
        $a = $DocumentsAttachmentsTable->find()
            ->select()
            ->where(['original' => 'sunset_uploaded.jpg'])
            ->first();

        $uploadFolder = Configure::read('Documents.uploadFolder');
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

        $this->get('/documents/documents-attachments/delete/aef61652-7416-43b4-9bb4-198f5706ed74');
        $this->assertRedirect(['controller' => 'DocumentsAttachments', 'action' => 'index']);
    }
}
