<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsTasksTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsTasksTable Test Case
 */
class ProjectsTasksTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsTasksTable
     */
    protected $ProjectsTasks;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ProjectsTasks') ? [] : ['className' => ProjectsTasksTable::class];
        $this->ProjectsTasks = $this->getTableLocator()->get('ProjectsTasks', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->ProjectsTasks);

        parent::tearDown();
    }
}
