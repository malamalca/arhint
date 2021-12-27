<?php
declare(strict_types=1);

namespace Crm\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * Crm\Model\Table\AdremasContactsTable Test Case
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
        'plugin.Crm.AdremasContacts',
        'plugin.Crm.Adremas',
        'plugin.Crm.ContactsAddresses',
        'plugin.Crm.Contacts',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::exists('AdremasContacts') ? [] : ['className' => 'Crm\Model\Table\AdremasContactsTable'];
        $this->AdremasContacts = TableRegistry::getTableLocator()->get('AdremasContacts', $config);
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
}
