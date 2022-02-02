<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use Calendar\Model\Table\EventsTable;

/**
 * Calendar\Model\Table\EventsTable Test Case
 */
class EventsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \Calendar\Model\Table\EventsTable
     */
    protected $Events;

    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.Calendar.Events',
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Events') ? [] : ['className' => EventsTable::class];
        $this->Events = $this->getTableLocator()->get('Events', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Events);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \Calendar\Model\Table\EventsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \Calendar\Model\Table\EventsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
