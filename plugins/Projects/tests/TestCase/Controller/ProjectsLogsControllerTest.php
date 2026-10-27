<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Projects\Controller\ProjectsLogsController Test Case
 */
class ProjectsLogsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'app.Users',
        'app.Logs',
        'Projects' => 'plugin.Projects.Projects',
    ];

    /**
     * Saving a log from a popup (XHR) must answer with the html of the log, not with a redirect to a full page.
     *
     * @return void
     */
    public function testEditPostViaPopupReturnsLogHtml(): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->session(['Auth' => $user]);
        $this->configRequest(['headers' => ['X-Requested-With' => 'XMLHttpRequest']]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $this->post('/projects/projects-logs/edit?project=' . $projectId, [
            'model' => 'Project',
            'action' => 'Comment',
            'foreign_id' => $projectId,
            'user_id' => USER_ADMIN,
            'descript' => '<p>popup log</p>',
        ]);

        $this->assertResponseOk();
        $this->assertResponseContains('<p>popup log</p>');
        $this->assertResponseNotContains('<html');
    }

    /**
     * A regular (non XHR) post still redirects to the logs of the project.
     *
     * @return void
     */
    public function testEditPostRedirects(): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get(USER_ADMIN);
        $this->session(['Auth' => $user]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $projectId = '4dd53305-9715-4be4-b169-20defe113d2a';
        $this->post('/projects/projects-logs/edit?project=' . $projectId, [
            'model' => 'Project',
            'action' => 'Comment',
            'foreign_id' => $projectId,
            'user_id' => USER_ADMIN,
            'descript' => '<p>regular log</p>',
        ]);

        $this->assertRedirectContains('/projects/projects/view/' . $projectId);
    }
}
