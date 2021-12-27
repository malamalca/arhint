<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Documents\Controller\DocumentsAttachmentsController Test Case
 */
class DocumentsAttachmentsControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'Documents' => 'plugin.Documents.Documents',
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

        $this->get('documents/documents-attachments/view/aef61652-7416-43b4-9bb4-198f5706ed74');

        $this->assertResponseOk();
    }

    /**
     * Test add method
     * vendor\bin\phpunit --filter testAdd plugins\Documents\tests\TestCase\Controller\DocumentsAttachmentsControllerTest.php
     *
     * @return void
     */
    public function testAdd()
    {
        $this->login(USER_ADMIN);

        $data = [
            'id' => null,
            'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'filename' => [
                'name' => 'sunset_uploaded.jpg',
                'type' => 'image/jpg',
                'size' => 100963,
                'tmp_name' => dirname(__FILE__) . DS . 'data' . DS . 'sunset.jpg',
            ],
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('documents/documents-attachments/add/d0d59a31-6de7-4eb4-8230-ca09113a7fe5', $data);

        $this->assertRedirect(['controller' => 'Documents', 'action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);

        $documents = TableRegistry::getTableLocator()->get('Documents.Documents');
        $document = $documents->get('d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertEquals($document->attachments_count, 2);

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

        $this->get('documents/documents-attachments/delete/aef61652-7416-43b4-9bb4-198f5706ed74');
        $this->assertRedirect(['controller' => 'Documents', 'action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']);
    }
}
