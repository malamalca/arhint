<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LilCrm\Model\Table\ContactsAddressesTable Test Case
 */
class ContactsAddressesTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'ContactsAddresses' => 'plugin.LilCrm.ContactsAddresses',
        'Contacts' => 'plugin.LilCrm.Contacts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::exists('ContactsAddresses') ? [] : ['className' => 'LilCrm\Model\Table\ContactsAddressesTable'];
        $this->ContactsAddresses = TableRegistry::get('ContactsAddresses', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ContactsAddresses);

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

    /**
     * Test buildRules method
     *
     * @return void
     */
    public function testBuildRules()
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
