<?php
namespace Crm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsAddressesFixture
 */
class ContactsAddressesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a41',
            'contact_id' => COMPANY_FIRST,
            'primary' => true,
            'kind' => 'W',
            'street' => 'Slakova ulica 36',
            'zip' => '8210',
            'city' => 'Trebnje',
            'country' => 'Slovenia',
            'created' => '2015-01-31 07:29:14',
            'modified' => '2015-01-31 07:29:14',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ed-ca50783b5a42',
            'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'primary' => true,
            'kind' => 'W',
            'street' => 'First Clients Rd 21',
            'zip' => '111',
            'city' => 'AcmeCity',
            'country' => 'AcMe',
            'created' => '2015-01-31 07:29:14',
            'modified' => '2015-01-31 07:29:14',
        ],
    ];
}
