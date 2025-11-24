<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsMilestonesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsMilestonesTable Test Case
 */
class ProjectsMilestonesTableTest extends TestCase
{
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
        $config = $this->getTableLocator()->exists('ProjectsMilestones') ? [] : ['className' => ProjectsMilestonesTable::class];
        $this->ProjectsMilestones = $this->getTableLocator()->get('ProjectsMilestones', $config);
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
}
