<?php
namespace LilInvoices\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesCountersFixture
 */
class InvoicesCountersFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'owner_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'kind' => ['type' => 'string', 'length' => 20, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'doc_type' => ['type' => 'string', 'fixed' => true, 'length' => 5, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'expense' => ['type' => 'string', 'length' => 20, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'counter' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'title' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'mask' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'layout' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'layout_title' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'template_descript' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'tpl_header_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'tpl_body_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'tpl_footer_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'active' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '1', 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB', 'collation' => 'utf8_unicode_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'kind' => 'received',
            'doc_type' => null,
            'expense' => 1,
            'counter' => 1,
            'title' => 'Received Invoices 2015',
            'mask' => null,
            'layout' => null,
            'layout_title' => 'Received [[no]]',
            'template_descript' => null,
            'active' => 1,
            'modified' => '2015-02-08 07:49:21',
            'created' => '2015-02-08 07:49:21',
        ],
        [
            'id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'owner_id' => '7d5c7465-9487-4203-ae67-ddb191c42816',
            'kind' => 'issued',
            'doc_type' => null,
            'expense' => 1,
            'counter' => 1,
            'title' => 'Issued Invoices 2015',
            'mask' => 'ISU-[[no.2]]',
            'layout' => null,
            'layout_title' => 'Issued [[no]]',
            'template_descript' => null,
            'active' => 1,
            'modified' => '2015-02-08 07:49:21',
            'created' => '2015-02-08 07:49:21',
        ],
    ];
}
