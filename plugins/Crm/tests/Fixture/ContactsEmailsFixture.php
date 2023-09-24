<?php
namespace Crm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsEmailsFixture
 */
class ContactsEmailsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => '1',
            'contact_id' => COMPANY_FIRST,
            'primary' => 1,
            'email' => 'info@arhim.si',
            'kind' => 'W',
            'created' => '2015-01-31 07:29:20',
            'modified' => '2015-01-31 07:29:20',
        ],
    ];
}
