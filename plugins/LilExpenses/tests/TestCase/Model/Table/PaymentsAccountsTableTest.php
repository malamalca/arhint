<?php
declare(strict_types=1);

namespace LilExpenses\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LilExpenses\Model\Table\PaymentsAccountsTable Test Case
 */
class PaymentsAccountsTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.LilExpenses.PaymentsAccounts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::exists('PaymentsAccounts') ? [] : ['className' => 'LilExpenses\Model\Table\PaymentsAccountsTable'];
        $this->PaymentsAccounts = TableRegistry::get('PaymentsAccounts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->PaymentsAccounts);

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
}
