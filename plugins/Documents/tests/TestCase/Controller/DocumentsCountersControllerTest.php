<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Documents\Controller\DocumentsCountersController Test Case
 */
class DocumentsCountersControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'DocumentsCounters' => 'plugin.Documents.DocumentsCounters',
        'DocumentsTemplates' => 'plugin.Documents.DocumentsTemplates',
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
        $this->get('/documents/documents-counters');
        $this->assertRedirect();

        $this->login(USER_ADMIN);
        $this->get('/documents/documents-counters');
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

        $this->get('/documents/documents-counters/edit', $data);
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents-counters/edit', $data);
        $this->assertRedirect(['controller' => 'DocumentsCounters', 'action' => 'index']);

        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $documents = $DocumentsCounters->find()->select()->where(['title' => 'Test Counter'])->all();
        $this->assertEquals(1, $documents->count());
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

        $this->get('/documents/documents-counters/edit/1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents-counters/edit/1d53bc5b-de2d-4e85-b13b-81b39a97fc88', $data);
        $this->assertRedirect(['controller' => 'DocumentsCounters', 'action' => 'index']);

        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $document = $DocumentsCounters->get('1d53bc5b-de2d-4e85-b13b-81b39a97fc88');
        $this->assertEquals('Edited Title From TestSuite', $document->title);
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $this->login(USER_ADMIN);

        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $countBefore = $DocumentsCounters->find()
            ->where(['owner_id' => COMPANY_FIRST])
            ->count();

        $this->get('/documents/documents-counters/delete/1d53bc5b-de2d-4e85-b13b-81b39a97fc88');
        $this->assertRedirect(['controller' => 'DocumentsCounters', 'action' => 'index']);

        $countAfter = $DocumentsCounters->find()
            ->where(['owner_id' => COMPANY_FIRST])
            ->count();

        $this->assertEquals($countBefore - 1, $countAfter);
    }
}
