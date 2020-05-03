<?php
declare(strict_types=1);

namespace LilCrm\Test\TestCase\Model\Table;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * LilCrm\Model\Table\ContactsTable Test Case
 */
class ContactsTableTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'Contacts' => 'plugin.LilCrm.Contacts',
        'Adremas' => 'plugin.LilCrm.Adremas',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $config = TableRegistry::exists('Contacts') ? [] : ['className' => 'LilCrm\Model\Table\ContactsTable'];
        $this->Contacts = TableRegistry::getTableLocator()->get('Contacts', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Contacts);

        parent::tearDown();
    }

    /**
     * Test name generation
     *
     * @return void
     */
    public function testTitleGeneration()
    {
        $contact = $this->Contacts->get('49a90cfe-fda4-49ca-b7ec-ca50783b5a45');
        $contact->name = 'Different';
        $contact->surname = 'Title';
        $contact = $this->Contacts->save($contact);

        $this->assertEquals('Title Different', $contact->title);
    }
}
