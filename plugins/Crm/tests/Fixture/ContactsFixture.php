<?php
namespace Crm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsFixture
 */
class ContactsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'C',
            'name' => null,
            'surname' => null,
            'title' => 'Arhim d.o.o.',
            'descript' => 'We brought you to this app.',
            'mat_no' => null,
            'tax_no' => 'SI55736645',
            'tax_status' => 1,
            'company_id' => null,
            'job' => null,
            //'syncable' => false,
            'created' => '2015-01-31 07:28:53',
            'modified' => '2015-01-31 07:28:53',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a42',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'T',
            'name' => 'Miha',
            'surname' => 'Nahtigal',
            'title' => 'Nahtigal Miha',
            'descript' => 'That\'s me!',
            'mat_no' => null,
            'tax_no' => null,
            'tax_status' => 0,
            'company_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'job' => 'CEO',
            //'syncable' => true,
            'created' => '2015-01-31 07:28:54',
            'modified' => '2015-01-31 07:28:54',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'owner_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a44',
            'kind' => 'C',
            'name' => null,
            'surname' => null,
            'title' => 'Client d.o.o.',
            'descript' => 'This is a first client',
            'mat_no' => null,
            'tax_no' => 'SI55736645',
            'tax_status' => 1,
            'company_id' => null,
            'job' => null,
            //'syncable' => false,
            'created' => '2015-01-31 07:28:53',
            'modified' => '2015-01-31 07:28:53',
        ],
        [
            'id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a45',
            'owner_id' => COMPANY_FIRST,
            'kind' => 'T',
            'name' => 'Second',
            'surname' => 'Person',
            'title' => 'Person Second',
            'descript' => 'That\'s not me!',
            'mat_no' => null,
            'tax_no' => null,
            'tax_status' => 0,
            'company_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'job' => 'Employee',
            //'syncable' => true,
            'created' => '2015-01-31 07:28:54',
            'modified' => '2015-01-31 07:28:54',
        ],
    ];
}
