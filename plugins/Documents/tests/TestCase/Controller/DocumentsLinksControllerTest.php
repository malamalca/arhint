<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestCase;

/**
 * Documents\Controller\DocumentsLinksController Test Case
 */
class DocumentsLinksControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Users' => 'app.Users',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'Documents' => 'plugin.Documents.Documents',
    ];

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
    public function testLink()
    {
        $this->get('/documents/documents-links/link/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        //$this->get('/documents/documents-links/link/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        //$this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->setUnlockedFields(['document_id']);

        $this->post(
            '/documents/DocumentsLinks/link/d0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            [
                'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                'referer' => '',
                'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                'title' => '',
            ]
        );

        $this->assertFlashElement('flash/success');
        $this->assertRedirect(Router::url(['controller' => 'Documents', 'action' => 'view', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5']));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $DocumentsLinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');
        $linkId = $DocumentsLinksTable->two('d0d59a31-6de7-4eb4-8230-ca09113a7fe5', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6');

        $this->login(USER_ADMIN);

        $this->get('/documents/documents-links/delete/d0d59a31-6de7-4eb4-8230-ca09113a7fe5/' . $linkId);
        $this->assertFlashElement('flash/success');
        $this->assertRedirect();

    }
}
