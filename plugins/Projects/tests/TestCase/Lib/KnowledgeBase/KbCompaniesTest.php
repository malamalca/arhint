<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use Projects\Lib\KnowledgeBase\KbSettings;
use Projects\Lib\KnowledgeBase\KbUpdater;
use Projects\Lib\KnowledgeBase\KbUpdateReport;

/**
 * The AI features of the knowledge base only for the companies that are listed.
 */
class KbCompaniesTest extends TestCase
{
    private const FIRST = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const SECOND = '4dd53305-9715-4be4-b169-20defe113d2b';
    private const OTHER_COMPANY = '11111111-2222-4333-8444-555555555555';

    /**
     * @var array<int, string>
     */
    public array $fixtures = [
        'app.Users',
        'app.Logs',
        'plugin.Projects.LogsAnalysis',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsMilestones',
        'plugin.Projects.ProjectsTasks',
        'plugin.Projects.ProjectsTasksComments',
        'plugin.Crm.Contacts',
        'plugin.Projects.ProjectsAuthorityCases',
        'plugin.Projects.ProjectsAuthorityEvents',
        'plugin.Projects.ProjectsKbSources',
        'plugin.Projects.ProjectsKbRevisions',
    ];

    /**
     * @var mixed
     */
    private mixed $companies;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->companies = Configure::read('Projects.KnowledgeBase.companies');
        $this->getTableLocator()->get('App.Logs')->deleteAll([]);
        $this->getTableLocator()->get('Projects.ProjectsMilestones')->deleteAll([]);
        // The second project belongs to another company.
        $this->getTableLocator()->get('Projects.Projects')
            ->updateAll(['owner_id' => self::OTHER_COMPANY], ['id' => self::SECOND]);
        foreach ([self::FIRST, self::SECOND] as $projectId) {
            $this->log($projectId);
        }
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        Configure::write('Projects.KnowledgeBase.companies', $this->companies);
        parent::tearDown();
    }

    /**
     * @param string $projectId Project.
     * @return void
     */
    private function log(string $projectId): void
    {
        $logs = $this->getTableLocator()->get('App.Logs');
        $log = $logs->newEntity([
            'id' => Text::uuid(),
            'model' => 'Project',
            'foreign_id' => $projectId,
            'user_id' => USER_ADMIN,
            'action' => 'Comment',
            'descript' => 'Dogodek projekta',
        ]);
        $log->created = new DateTime('2026-10-20 10:00:00');
        $log->modified = new DateTime('2026-10-20 10:00:00');
        $logs->saveOrFail($log, ['checkRules' => false]);
    }

    /**
     * @return void
     */
    public function testNoCompaniesMeansEveryCompany(): void
    {
        Configure::write('Projects.KnowledgeBase.companies', []);

        $this->assertSame([], KbSettings::companies());
        $this->assertTrue(KbSettings::companyAllowed(COMPANY_FIRST));
        $this->assertTrue(KbSettings::companyAllowed(self::OTHER_COMPANY));
        $this->assertTrue(KbSettings::companyAllowed(null));
    }

    /**
     * @return void
     */
    public function testListedCompaniesOnly(): void
    {
        Configure::write('Projects.KnowledgeBase.companies', [COMPANY_FIRST, '']);

        $this->assertSame([COMPANY_FIRST], KbSettings::companies());
        $this->assertTrue(KbSettings::companyAllowed(COMPANY_FIRST));
        $this->assertFalse(KbSettings::companyAllowed(self::OTHER_COMPANY));
        $this->assertFalse(KbSettings::companyAllowed(null));
    }

    /**
     * @return void
     */
    public function testPeriodicUpdateLooksOnlyAtProjectsOfTheListedCompanies(): void
    {
        Configure::write('Projects.KnowledgeBase.companies', [COMPANY_FIRST]);
        $ai = new FakeKbAiClient();

        $report = (new KbUpdater($ai))->run(['projects' => 10]);

        $this->assertSame(1, $report->projects);
        $this->assertCount(1, $ai->requests);
        $projects = $this->getTableLocator()->get('Projects.Projects');
        $this->assertSame(1, (int)$projects->get(self::FIRST)->kb_format);
        $this->assertSame(0, (int)$projects->get(self::SECOND)->kb_format);

        // Without the limit the other company is looked at too.
        Configure::write('Projects.KnowledgeBase.companies', []);
        $report = (new KbUpdater(new FakeKbAiClient()))->run(['projects' => 10]);
        $this->assertSame(1, $report->projects);
        $this->assertSame(1, (int)$projects->get(self::SECOND)->kb_format);
    }

    /**
     * @return void
     */
    public function testNamedProjectOfAnotherCompanyIsRefusedWithAnExplanation(): void
    {
        Configure::write('Projects.KnowledgeBase.companies', [COMPANY_FIRST]);
        $ai = new FakeKbAiClient();

        $report = (new KbUpdater($ai))->run(['project' => self::SECOND]);

        $this->assertSame(0, $report->projects);
        $this->assertSame([], $ai->requests);
        $this->assertStringContainsString('not on for its company', implode("\n", $report->messages));
    }

    /**
     * @return void
     */
    public function testRegenerationIsRefusedForAnotherCompany(): void
    {
        Configure::write('Projects.KnowledgeBase.companies', [COMPANY_FIRST]);
        $ai = new FakeKbAiClient();
        $project = $this->getTableLocator()->get('Projects.Projects')->get(self::SECOND);
        $report = new KbUpdateReport();

        $made = (new KbUpdater($ai))->regenerate($project, $report);

        $this->assertFalse($made);
        $this->assertSame([], $ai->requests);
        $this->assertStringContainsString('not on for the company', implode("\n", $report->messages));
    }
}
