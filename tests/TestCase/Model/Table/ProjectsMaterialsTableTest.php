<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsMaterialsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsMaterialsTable Test Case
 */
class ProjectsMaterialsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsMaterialsTable
     */
    protected $ProjectsMaterials;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ProjectsMaterials') ? [] : ['className' => ProjectsMaterialsTable::class];
        $this->ProjectsMaterials = $this->getTableLocator()->get('ProjectsMaterials', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ProjectsMaterials);

        parent::tearDown();
    }
}
