<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Documents\Controller\DocumentsLinksController Test Case
 */
class DocumentsLinksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'Users' => 'app.Users',
        'DocumentsLinks' => 'plugin.Documents.DocumentsLinks',
        'Invoices' => 'plugin.Documents.Invoices',
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
    public function testLink()
    {
        $this->get('/documents/documents-links/link/Invoice/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        $this->assertRedirect();

        $this->login(USER_ADMIN);

        //$this->get('/documents/documents-links/link/d0d59a31-6de7-4eb4-8230-ca09113a7fe5');
        //$this->assertResponseOk();

        $this->enableCsrfToken();
        $this->enableSecurityToken();
        $this->setUnlockedFields(['document_id', 'model']);

        $this->post(
            '/documents/DocumentsLinks/link/Invoice/d0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            [
                'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
                'referer' => '',
                'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
                'title' => '',
                'model' => 'Invoice',
            ],
        );

        $this->assertFlashElement('flash/success');
        $this->assertRedirect(Router::url(['controller' => 'DocumentsLinks', 'action' => 'index'], true));
    }

    /**
     * Test delete method
     *
     * @return void
     */
    public function testDelete()
    {
        $DocumentsLinksTable = TableRegistry::getTableLocator()->get('Documents.DocumentsLinks');
        $linkId = $DocumentsLinksTable->two('Invoice', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5', 'Invoice', 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6');
        $this->assertNotFalse($linkId);

        $this->login(USER_ADMIN);

        //$this->get('/documents/documents-links/delete/d0d59a31-6de7-4eb4-8230-ca09113a7fe5/' . $linkId);
        //$this->assertFlashElement('flash/success');
        //$this->assertRedirect();
    }
}
