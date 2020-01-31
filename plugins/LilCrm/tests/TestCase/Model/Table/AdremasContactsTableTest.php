<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LilCrm\Model\Table\AdremasContactsTable Test Case
 */
class AdremasContactsTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'app.Users',
        'plugin.LilCrm.AdremasContacts',
        'plugin.LilCrm.Adremas',
        'plugin.LilCrm.ContactsAddresses',
        'plugin.LilCrm.Contacts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::exists('AdremasContacts') ? [] : ['className' => 'LilCrm\Model\Table\AdremasContactsTable'];
        $this->AdremasContacts = TableRegistry::get('AdremasContacts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->AdremasContacts);

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
