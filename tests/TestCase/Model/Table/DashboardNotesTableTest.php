<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\DashboardNotesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\DashboardNotesTable Test Case
 */
class DashboardNotesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\DashboardNotesTable
     */
    protected $DashboardNotes;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('DashboardNotes')
            ? []
            : ['className' => DashboardNotesTable::class];
        $this->DashboardNotes = $this->getTableLocator()->get('DashboardNotes', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->DashboardNotes);
        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\DashboardNotesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        // Valid entity has no errors
        $note = $this->DashboardNotes->newEntity([
            'user_id' => USER_ADMIN,
            'note' => 'This is a valid note',
        ]);
        $this->assertEmpty($note->getErrors(), 'Valid entity should have no errors');

        // note empty string fails (notEmptyString)
        $note = $this->DashboardNotes->newEntity([
            'user_id' => USER_ADMIN,
            'note' => '',
        ]);
        $this->assertArrayHasKey('note', $note->getErrors());

        // note absent is fine – no requirePresence on this field
        $note = $this->DashboardNotes->newEntity([
            'user_id' => USER_ADMIN,
        ]);
        $this->assertArrayNotHasKey('note', $note->getErrors());
    }

    /**
     * Test that a valid note can be saved and retrieved
     *
     * @return void
     */
    public function testSaveAndRetrieve(): void
    {
        $note = $this->DashboardNotes->newEntity([
            'user_id' => USER_ADMIN,
            'note' => 'Test dashboard note',
        ]);
        $saved = $this->DashboardNotes->save($note);
        $this->assertNotFalse($saved, 'Valid note should save without errors');
        $this->assertNotEmpty($saved->id, 'Saved note should have an ID');

        $found = $this->DashboardNotes->get($saved->id);
        $this->assertEquals('Test dashboard note', $found->note);
        $this->assertEquals(USER_ADMIN, $found->user_id);
    }
}
