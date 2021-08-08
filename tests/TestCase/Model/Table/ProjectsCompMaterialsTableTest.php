<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsCompMaterialsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsCompMaterialsTable Test Case
 */
class ProjectsCompMaterialsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsCompMaterialsTable
     */
    protected $ProjectsCompMaterials;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ProjectsCompMaterials') ? [] : ['className' => ProjectsCompMaterialsTable::class];
        $this->ProjectsCompMaterials = $this->getTableLocator()->get('ProjectsCompMaterials', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ProjectsCompMaterials);

        parent::tearDown();
    }
}
