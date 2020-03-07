<?php
namespace LilInvoices\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesFixture
 *
 */
class InvoicesFixture extends TestFixture
{
    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'owner_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'user_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'contact_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'counter_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'project_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'doc_type' => ['type' => 'string', 'fixed' => true, 'length' => 5, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'tpl_header_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'tpl_body_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'tpl_footer_id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'invoices_attachment_count' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => false, 'default' => '0', 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'counter' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'no' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'title' => ['type' => 'string', 'length' => 200, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'descript' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'signed' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'dat_sign' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'dat_issue' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'dat_service' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'dat_expire' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'dat_approval' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'net_total' => ['type' => 'decimal', 'length' => 15, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'total' => ['type' => 'decimal', 'length' => 15, 'precision' => 2, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => ''],
        'inversed_tax' => ['type' => 'boolean', 'length' => null, 'null' => false, 'default' => '1', 'comment' => '', 'precision' => null],

        'pmt_kind' => ['type' => 'integer', 'length' => 11, 'unsigned' => false, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'autoIncrement' => null],
        'pmt_sepa_type' => ['type' => 'string', 'fixed' => true, 'length' => 10, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'pmt_type' => ['type' => 'string', 'fixed' => true, 'length' => 10, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'pmt_module' => ['type' => 'string', 'fixed' => true, 'length' => 4, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'pmt_ref' => ['type' => 'string', 'length' => 35, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'pmt_descript' => ['type' => 'string', 'length' => 140, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],

        'location' => ['type' => 'string', 'length' => 70, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],

        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => ['engine' => 'InnoDB', 'collation' => 'utf8_unicode_ci'],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            // received invoice
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'owner_id' => COMPANY_FIRST,
            //'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'doc_type' => 'IV',
            'tpl_header_id' => null,
            'tpl_body_id' => null,
            'tpl_footer_id' => null,

            'invoices_attachment_count' => 0,
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'First received invoice',
            'descript' => 'Usually empty in received invoices',

            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'net_total' => 100,
            'total' => 122,
            'inversed_tax' => false,

            'pmt_kind' => 0,
            'pmt_sepa_type' => 'OTHR',
            'pmt_type' => 'SI',
            'pmt_module' => '12',
            'pmt_ref' => '1234',

            'location' => 'Ljubljana',
            'created' => '2015-02-08 06:34:20',
            'modified' => '2015-02-08 06:34:20',
        ],
        [
            // issued invoice
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            //'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'doc_type' => 'IV',

            'invoices_attachment_count' => 0,
            'counter' => 1,
            'no' => 'ISU-01',
            'title' => 'First issued invoice',
            'descript' => 'Payment details etc',

            'dat_issue' => '2015-02-08',
            'dat_service' => '2015-02-08',
            'dat_expire' => '2015-02-16',
            'dat_approval' => null,
            'net_total' => 290,
            'total' => 353.8,
            'inversed_tax' => false,

            'pmt_kind' => 0,
            'pmt_sepa_type' => 'OTHR',
            'pmt_type' => 'SI',
            'pmt_module' => '00',
            'pmt_ref' => '01',

            'location' => 'Ljubljana',
            'created' => '2015-02-08 06:34:20',
            'modified' => '2015-02-08 06:34:20',
        ],
    ];
}
