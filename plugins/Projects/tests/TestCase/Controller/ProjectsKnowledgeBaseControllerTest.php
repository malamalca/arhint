<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbService;

/**
 * Knowledge base on the project pages: reading, hand editing, history and return to a version.
 */
class ProjectsKnowledgeBaseControllerTest extends TestCase
{
    use IntegrationTestTrait;

    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';

    /**
     * @var array<int|string, string>
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsUsers',
        'plugin.Projects.ProjectsKbRevisions',
    ];

    /**
     * @param string $userId User id.
     * @return void
     */
    private function login(string $userId): void
    {
        $this->session(['Auth' => TableRegistry::getTableLocator()->get('Users')->get($userId)]);
    }

    /**
     * @return \Projects\Model\Entity\Project
     */
    private function project(): object
    {
        return TableRegistry::getTableLocator()->get('Projects.Projects')->get(self::PROJECT);
    }

    /**
     * @param string $text Warning text.
     * @return void
     */
    private function addWarning(string $text): void
    {
        (new KbService())->apply(
            self::PROJECT,
            [['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => $text]],
            KbOperationApplier::BATCH,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(string $descript, int $version): array
    {
        return [
            'id' => self::PROJECT,
            'owner_id' => COMPANY_FIRST,
            'no' => '2022-01',
            'title' => 'First Project Title',
            'active' => 1,
            'descript' => $descript,
            'kb_version' => $version,
        ];
    }

    /**
     * @return void
     */
    public function testViewShowsKnowledgeBaseWithoutStructureComments(): void
    {
        $this->addWarning('Manjka PGD');
        $this->login(USER_ADMIN);

        $this->get('/projects/projects/view/' . self::PROJECT);

        $this->assertResponseOk();
        $this->assertResponseContains('Manjka PGD');
        $this->assertResponseContains('id="kb-opozorila"');
        $this->assertResponseContains('/projects/projects/history/' . self::PROJECT);
        $this->assertResponseNotContains('kb:item');
        $this->assertResponseNotContains('kb:section');
    }

    /**
     * @return void
     */
    public function testEditShowsVersionOfKnowledgeBase(): void
    {
        $this->addWarning('Manjka PGD');
        $this->login(USER_ADMIN);

        $this->get('/projects/projects/edit/' . self::PROJECT);

        $this->assertResponseOk();
        $this->assertResponseContains('name="kb_version"');
        $this->assertResponseContains('value="2"');
        $this->assertResponseContains('kb:item');
    }

    /**
     * @return void
     */
    public function testEditSavesDescriptionAsKnowledgeBase(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/edit/' . self::PROJECT, $this->formData('Nov opis projekta', 0));

        $this->assertRedirect(['action' => 'view', self::PROJECT]);
        $project = $this->project();
        $this->assertSame(1, $project->kb_format);
        $this->assertSame('Nov opis projekta', KbDocument::parse($project->descript)->text('description'));
    }

    /**
     * @return void
     */
    public function testEditKeepsKnowledgeBaseWhenSubmittedBlank(): void
    {
        $this->addWarning('Manjka PGD');
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/edit/' . self::PROJECT, $this->formData('', 2));

        $this->assertRedirect(['action' => 'view', self::PROJECT]);
        $project = $this->project();
        $this->assertSame(2, $project->kb_version);
        $this->assertCount(1, KbDocument::parse($project->descript)->items('warnings'));
    }

    /**
     * @return void
     */
    public function testEditRefusesOutdatedFormAndKeepsTypedText(): void
    {
        $this->addWarning('Manjka PGD');
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $before = $this->project()->descript;

        $data = $this->formData('Moj stari obrazec', 0);
        $data['title'] = 'Spremenjen naslov';
        $this->post('/projects/projects/edit/' . self::PROJECT, $data);

        $this->assertResponseOk();
        $this->assertResponseContains('Moj stari obrazec');
        // The form now carries the current version, but nothing was saved.
        $this->assertResponseContains('value="2"');
        $project = $this->project();
        $this->assertSame($before, $project->descript);
        $this->assertSame('First Project Title', $project->title);
    }

    /**
     * @return void
     */
    public function testHistoryListsRevisionsAndShowsChanges(): void
    {
        $this->addWarning('Manjka PGD');
        $this->login(USER_ADMIN);

        $this->get('/projects/projects/history/' . self::PROJECT);
        $this->assertResponseOk();
        $this->assertResponseContains('AI');

        $this->get('/projects/projects/history/' . self::PROJECT . '?version=2');
        $this->assertResponseOk();
        $this->assertResponseContains('Manjka PGD');
        $this->assertResponseContains('+ ');
    }

    /**
     * @return void
     */
    public function testHistoryIsNotOpenToNonMembers(): void
    {
        $this->login(USER_COMMON);

        $this->get('/projects/projects/history/' . self::PROJECT);

        $this->assertResponseError();
    }

    /**
     * @return void
     */
    public function testRevertReturnsToEarlierVersion(): void
    {
        $this->addWarning('Prvo');
        $revisions = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions');
        $first = $revisions->find()->where(['version' => 2])->firstOrFail();
        $this->addWarning('Drugo');
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $this->post('/projects/projects/revert/' . $first->id);

        $this->assertRedirect(['action' => 'history', self::PROJECT]);
        $doc = KbDocument::parse($this->project()->descript);
        $this->assertCount(1, $doc->items('warnings'));
        $this->assertSame('Prvo', $doc->items('warnings')[0]->text);
    }

    /**
     * @return void
     */
    public function testRevertNeedsPostAndMembership(): void
    {
        $this->addWarning('Prvo');
        $first = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions')->find()->firstOrFail();

        $this->login(USER_ADMIN);
        $this->get('/projects/projects/revert/' . $first->id);
        $this->assertResponseError();

        $this->login(USER_COMMON);
        $this->enableSecurityToken();
        $this->enableCsrfToken();
        $this->post('/projects/projects/revert/' . $first->id);
        $this->assertResponseError();
        $this->assertSame(2, $this->project()->kb_version);
    }
}
