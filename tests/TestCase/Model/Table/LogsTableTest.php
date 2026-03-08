<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\LogsTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\LogsTable Test Case
 */
class LogsTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\LogsTable
     */
    protected $Logs;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Logs',
        'app.Users',
        'plugin.Crm.Contacts',
        'plugin.Documents.Documents',
        'plugin.Projects.Projects',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Logs') ? [] : ['className' => LogsTable::class];
        $this->Logs = $this->getTableLocator()->get('Logs', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Logs);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\LogsTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        // All nullable fields: valid entity has no errors
        $log = $this->Logs->newEntity([
            'model' => 'TestModel',
            'foreign_id' => '11111111-1111-4111-8111-111111111111',
            'user_id' => USER_ADMIN,
            'descript' => 'Some description',
        ]);
        $this->assertEmpty($log->getErrors(), 'Valid entity should have no validation errors');

        // model exceeds maxLength(50)
        $log = $this->Logs->newEntity([
            'model' => str_repeat('a', 51),
        ]);
        $this->assertArrayHasKey('model', $log->getErrors());
        $this->assertArrayHasKey('maxLength', $log->getErrors()['model']);

        // foreign_id must be a valid UUID
        $log = $this->Logs->newEntity([
            'foreign_id' => 'not-a-uuid',
        ]);
        $this->assertArrayHasKey('foreign_id', $log->getErrors());

        // user_id must be a valid UUID
        $log = $this->Logs->newEntity([
            'user_id' => 'not-a-uuid',
        ]);
        $this->assertArrayHasKey('user_id', $log->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\LogsTable::buildRules()
     */
    public function testBuildRules(): void
    {
        // Existing user_id passes and the record saves
        $log = $this->Logs->newEntity([
            'model' => 'Test',
            'user_id' => USER_ADMIN,
            'descript' => 'Test log',
        ]);
        $this->assertNotFalse($this->Logs->save($log), 'Save with valid user_id should succeed');

        // Non-existent user_id fails the existsIn rule
        $log = $this->Logs->newEntity([
            'model' => 'Test',
            'user_id' => '00000000-dead-beef-0000-000000000000',
            'descript' => 'Test log with bad user_id',
        ]);
        $this->assertFalse($this->Logs->save($log), 'Save with non-existent user_id should fail');
        $this->assertArrayHasKey('user_id', $log->getErrors());
    }
}
