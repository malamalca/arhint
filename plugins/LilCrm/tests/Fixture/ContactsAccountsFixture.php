<?php
namespace LilCrm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsAccountsFixture
 */
class ContactsAccountsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'contact_id' => COMPANY_FIRST,
            'primary' => 1,
            'kind' => 'W',
            'bban' => 'SI56 2420 3901 0691 883',
            'bic' => 'KREKSI22',
            'created' => '2015-01-31 07:29:08',
            'modified' => '2015-01-31 07:29:08',
        ],
    ];
}
