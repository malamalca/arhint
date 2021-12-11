<?php
declare(strict_types=1);

namespace LilProjects\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use LilProjects\Model\Table\ProjectsLogsTable;

/**
 * LilProjects\Model\Table\ProjectsLogsTable Test Case
 */
class ProjectsLogsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \LilProjects\Model\Table\ProjectsLogsTable
     */
    public $ProjectsLogs;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.LilProjects.ProjectsLogs',
        'plugin.LilProjects.Projects',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::getTableLocator()->exists('ProjectsLogs') ? [] : ['className' => ProjectsLogsTable::class];
        $this->ProjectsLogs = TableRegistry::getTableLocator()->get('ProjectsLogs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ProjectsLogs);

        parent::tearDown();
    }

    /**
     * Test initialize method
     *
     * @return void
     */
    public function testInitialize()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test validationDefault method
     *
     * @return void
     */
    public function testValidationDefault()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
