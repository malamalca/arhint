<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Projects\Model\Table\ProjectsTable;

/**
 * Projects\Model\Table\ProjectsTable Test Case
 */
class ProjectsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Projects\Model\Table\ProjectsTable
     */
    public $Projects;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Projects.Projects',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::exists('Projects') ? [] : ['className' => ProjectsTable::class];
        $this->Projects = TableRegistry::getTableLocator()->get('Projects', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Projects);

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
