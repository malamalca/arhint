<?php
namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DocumentsTaxesFixture
 */
class DocumentsTaxesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 1,
            'document_id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'vat_id' => '3e55df84-9fba-4ea7-ba9e-3e6a3f83da0c',
            'vat_title' => 'DDV 22%',
            'vat_percent' => 22,
            'base' => 100,
            'created' => '2015-02-08 06:34:31',
            'modified' => '2015-02-08 06:34:31',
        ],
    ];
}
