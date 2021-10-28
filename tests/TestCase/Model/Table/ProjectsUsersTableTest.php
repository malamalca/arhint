<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsUsersTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsUsersTable Test Case
 */
class ProjectsUsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsUsersTable
     */
    protected $ProjectsUsers;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ProjectsUsers') ? [] : ['className' => ProjectsUsersTable::class];
        $this->ProjectsUsers = $this->getTableLocator()->get('ProjectsUsers', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ProjectsUsers);

        parent::tearDown();
    }
}
