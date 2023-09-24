<?php
declare(strict_types=1);

namespace Tasks\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Tasks\Model\Table\TasksFoldersTable Test Case
 */
class TasksFoldersTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public array $fixtures = [
        'plugin.Tasks.TasksFolders',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->TasksFolders = TableRegistry::getTableLocator()->get('TasksFolders', ['className' => 'Tasks\Model\Table\TasksFoldersTable']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->TasksFolders);

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
