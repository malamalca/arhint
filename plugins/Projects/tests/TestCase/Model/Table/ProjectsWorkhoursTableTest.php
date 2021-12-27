<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Projects\Model\Table\ProjectsWorkhoursTable;

/**
 * Projects\Model\Table\ProjectsWorkhoursTable Test Case
 */
class ProjectsWorkhoursTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Projects\Model\Table\ProjectsWorkhoursTable
     */
    public $ProjectsWorkhours;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.Projects.ProjectsWorkhours',
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
        $config = TableRegistry::exists('ProjectsWorkhours') ? [] : ['className' => ProjectsWorkhoursTable::class];
        $this->ProjectsWorkhours = TableRegistry::getTableLocator()->get('ProjectsWorkhours', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ProjectsWorkhours);

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
