<?php
declare(strict_types=1);

namespace LilProjects\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use LilProjects\Model\Table\ProjectsWorkhoursTable;

/**
 * LilProjects\Model\Table\ProjectsWorkhoursTable Test Case
 */
class ProjectsWorkhoursTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \LilProjects\Model\Table\ProjectsWorkhoursTable
     */
    public $ProjectsWorkhours;

    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.lil_projects.projects_workhours',
        'plugin.lil_projects.projects',
        'plugin.lil_projects.users',
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
        $this->ProjectsWorkhours = TableRegistry::get('ProjectsWorkhours', $config);
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
