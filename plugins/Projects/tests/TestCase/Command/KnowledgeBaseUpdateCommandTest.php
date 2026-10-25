<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Command;

use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\TestSuite\TestCase;
use Projects\Event\KnowledgeBaseEvents;

/**
 * The command and the hourly heartbeat listener, with projects that have no new events (so no AI is asked).
 */
class KnowledgeBaseUpdateCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

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
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->get('App.Logs')->deleteAll([]);
        $this->getTableLocator()->get('Projects.ProjectsMilestones')->deleteAll([]);
    }

    /**
     * @return void
     */
    public function testCommandRunsAndReports(): void
    {
        $this->exec('knowledge_base update --dry-run --project 2022-01');

        $this->assertExitSuccess();
        $this->assertOutputContains('0 projects, 0 events read, 0 revisions written, 0 events failed.');
    }

    /**
     * @return void
     */
    public function testHelpListsOptions(): void
    {
        $this->exec('knowledge_base update --help');

        $this->assertExitSuccess();
        $this->assertOutputContains('--max-events');
        $this->assertOutputContains('--dry-run');
    }

    /**
     * @return void
     */
    public function testHeartbeatListenerIsRegisteredAndOffByDefault(): void
    {
        $listener = new KnowledgeBaseEvents();
        $this->assertSame(['App.HeartBeat.hourlyRun' => 'hourlyRun'], $listener->implementedEvents());
        $this->assertEmpty(Configure::read('Projects.KnowledgeBase.enabled'));

        // Off: nothing is created, not even the lock.
        if (file_exists(TMP . 'projects_knowledge_base.lock')) {
            unlink(TMP . 'projects_knowledge_base.lock');
        }
        $listener->hourlyRun(new Event('App.HeartBeat.hourlyRun', $this));
        $this->assertFileDoesNotExist(TMP . 'projects_knowledge_base.lock');
    }

    /**
     * @return void
     */
    public function testHeartbeatListenerRunsWhenEnabledAndSurvivesTheLock(): void
    {
        Configure::write('Projects.KnowledgeBase.enabled', true);

        $listener = new KnowledgeBaseEvents();
        $listener->hourlyRun(new Event('App.HeartBeat.hourlyRun', $this));
        $this->assertFileExists(TMP . 'projects_knowledge_base.lock');

        // A previous run that still holds the lock: skipped, no error.
        $lock = fopen(TMP . 'projects_knowledge_base.lock', 'c');
        $this->assertTrue(flock($lock, LOCK_EX | LOCK_NB));
        $listener->hourlyRun(new Event('App.HeartBeat.hourlyRun', $this));
        flock($lock, LOCK_UN);
        fclose($lock);

        Configure::write('Projects.KnowledgeBase.enabled', false);
    }
}
