<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Documents\Controller\DocumentsTemplatesController Test Case
 */
class DocumentsTemplatesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'DocumentsTemplates' => 'plugin.Documents.DocumentsTemplates',
    ];

    /**
     * User login method
     *
     * @param string $userId User id
     * @return void
     */
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

        $this->get('/documents/DocumentsTemplates/index');
        $this->assertResponseOk();
    }

    /**
     * Test add method
     *
     * @return void
     */
    public function testAdd()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => '',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'header',
            'body' => 'New Template',
            'main' => true,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents-templates/edit', $data);
        $this->assertRedirect();
    }

    /**
     * Test edit method
     *
     * @return void
     */
    public function testEdit()
    {
        // Set session data
        $this->login(USER_ADMIN);

        $data = [
            'id' => 'a08d3c00-7443-40e0-ac62-0caca1747e24',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'header',
            'body' => 'Renamed',
            'main' => true,
        ];

        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/documents/documents-templates/edit/a08d3c00-7443-40e0-ac62-0caca1747e24', $data);
        $this->assertRedirect();
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

        $this->get('/documents/documents-templates/delete/a08d3c00-7443-40e0-ac62-0caca1747e24');
        $this->assertRedirect();
    }
}
