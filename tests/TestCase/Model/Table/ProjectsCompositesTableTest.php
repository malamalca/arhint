<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\ProjectsCompositesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\ProjectsCompositesTable Test Case
 */
class ProjectsCompositesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\ProjectsCompositesTable
     */
    protected $ProjectsComposites;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('ProjectsComposites') ? [] : ['className' => ProjectsCompositesTable::class];
        $this->ProjectsComposites = $this->getTableLocator()->get('ProjectsComposites', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ProjectsComposites);

        parent::tearDown();
    }
}
