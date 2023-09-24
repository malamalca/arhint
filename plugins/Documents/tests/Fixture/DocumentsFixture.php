<?php
namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DocumentsFixture
 */
class DocumentsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            // received document
            'id' => 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6',
            'owner_id' => COMPANY_FIRST,
            'counter_id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90',
            'tpl_header_id' => null,
            'tpl_body_id' => null,
            'tpl_footer_id' => null,

            'attachments_count' => 0,
            'counter' => 1,
            'no' => 'Issued Doc 1',
            'title' => 'First issued document',
            'descript' => 'This is a test',

            'dat_issue' => '2015-02-08',

            'location' => 'Ljubljana',
            'created' => '2015-02-08 06:34:20',
            'modified' => '2015-02-08 06:34:20',
        ],
    ];
}
