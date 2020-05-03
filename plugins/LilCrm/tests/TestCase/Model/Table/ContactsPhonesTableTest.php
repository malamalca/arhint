<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LilCrm\Model\Table\ContactsPhonesTable Test Case
 */
class ContactsPhonesTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'ContactsPhones' => 'plugin.LilCrm.ContactsPhones',
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
        $config = TableRegistry::exists('ContactsPhones') ? [] : ['className' => 'LilCrm\Model\Table\ContactsPhonesTable'];
        $this->ContactsPhones = TableRegistry::getTableLocator()->get('ContactsPhones', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->ContactsPhones);

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
