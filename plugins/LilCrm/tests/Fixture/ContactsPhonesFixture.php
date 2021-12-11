<?php
namespace LilCrm\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ContactsPhonesFixture
 */
class ContactsPhonesFixture extends TestFixture
{
   /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '1',
            'contact_id' => COMPANY_FIRST,
            'no' => '041 891 824',
            'kind' => 'W',
            'primary' => 1,
            'created' => '2015-01-31 07:29:24',
            'modified' => '2015-01-31 07:29:24',
        ],
    ];
}
