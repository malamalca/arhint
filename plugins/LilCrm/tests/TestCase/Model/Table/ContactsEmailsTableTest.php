<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LilCrm\Model\Table\ContactsEmailsTable Test Case
 */
class ContactsEmailsTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'ContactsEmails' => 'plugin.LilCrm.ContactsEmails',
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
        $config = TableRegistry::exists('ContactsEmails') ? [] : ['className' => 'LilCrm\Model\Table\ContactsEmailsTable'];
        $this->ContactsEmails = TableRegistry::getTableLocator()->get('ContactsEmails', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ContactsEmails);

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
