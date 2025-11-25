<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsMilestonesTable;
use Cake\Event\EventManager;
use Cake\TestSuite\TestCase;
use Projects\Event\ProjectsEvents;

/**
 * App\Model\Table\ProjectsMilestonesTable Test Case
 */
class ProjectsMilestonesTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsMilestones',
        'plugin.Projects.ProjectsTasks',
    ];

    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsMilestonesTable
     */
    protected $ProjectsMilestones;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Projects.ProjectsMilestones') ? [] : ['className' => ProjectsMilestonesTable::class];
        $this->ProjectsMilestones = $this->getTableLocator()->get('Projects.ProjectsMilestones', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ProjectsMilestones);

        parent::tearDown();
    }

    public function testTaskCounters()
    {
        $milestone = $this->ProjectsMilestones->newEmptyEntity();
        $milestone->project_id = '4dd53305-9715-4be4-b169-20defe113d2a';
        $milestone->title = 'Milestone with Tasks';
        $this->ProjectsMilestones->save($milestone);
        $this->assertEquals(0, $milestone->tasks_open);
        $this->assertEquals(0, $milestone->tasks_done);

        $task1 = $this->getTableLocator()->get('Projects.ProjectsTasks')->newEmptyEntity();
        $task1->project_id = '4dd53305-9715-4be4-b169-20defe113d2a';
        $task1->milestone_id = $milestone->id;
        $task1->date_complete = null;
        $task1->title = 'First Task';
        $this->getTableLocator()->get('Projects.ProjectsTasks')->save($task1);

        $milestone = $this->ProjectsMilestones->get($milestone->id);

        $this->assertEquals(1, $milestone->tasks_open);
        $this->assertEquals(0, $milestone->tasks_done);

        $task1->date_complete = date('Y-m-d H:i:s');
        $this->getTableLocator()->get('Projects.ProjectsTasks')->save($task1);

        $milestone = $this->ProjectsMilestones->get($milestone->id);
        $this->assertEquals(0, $milestone->tasks_open);
        $this->assertEquals(1, $milestone->tasks_done);

        $this->getTableLocator()->get('Projects.ProjectsTasks')->delete($task1);

        $milestone = $this->ProjectsMilestones->get($milestone->id);

        $this->assertEquals(0, $milestone->tasks_open);
        $this->assertEquals(0, $milestone->tasks_done);
    }
}
