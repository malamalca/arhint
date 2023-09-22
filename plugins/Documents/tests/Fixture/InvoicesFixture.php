<?php
namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * InvoicesFixture
 */
class InvoicesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            // received document
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe5',
            'owner_id' => COMPANY_FIRST,
            //'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'doc_type' => 'IV',
            'tpl_header_id' => null,
            'tpl_body_id' => null,
            'tpl_footer_id' => null,

            'attachments_count' => 0,
            'counter' => 1,
            'no' => 'R-cust-012',
            'title' => 'First received document',
            'descript' => 'Usually empty in received documents',

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
            // issued document
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            //'contact_id' => '49a90cfe-fda4-49ca-b7ec-ca50783b5a43',
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'doc_type' => 'IV',

            'attachments_count' => 0,
            'counter' => 1,
            'no' => 'ISU-01',
            'title' => 'First issued document',
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
