<?php
namespace Documents\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * DocumentsCountersFixture
 */
class DocumentsCountersFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc88',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'kind' => 'Invoices',
            'direction' => 'received',
            'doc_type' => null,
            'expense' => 1,
            'counter' => 1,
            'title' => 'Received Invoices 2015',
            'mask' => null,
            'layout' => null,
            'layout_title' => 'Received  [[no]]',
            'template_descript' => null,
            'active' => 1,
            'modified' => '2015-02-08 07:49:21',
            'created' => '2015-02-08 07:49:21',
        ],
        [
            'id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc89',
            'owner_id' => '7d5c7465-9487-4203-ae67-ddb191c42816',
            'kind' => 'Invoices',
            'direction' => 'issued',
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
        [
            'id' => '1d53bc5b-de2d-4e85-b13b-81b39a97fc90',
            'owner_id' => '8155426d-2302-4fa5-97de-e33cefb9d704',
            'kind' => 'Documents',
            'direction' => 'issued',
            'doc_type' => null,
            'expense' => 1,
            'counter' => 1,
            'title' => 'Issued Documents 2015',
            'mask' => 'DOC-[[no.2]]',
            'layout' => null,
            'layout_title' => 'Issued Doc [[no]]',
            'template_descript' => null,
            'active' => 1,
            'modified' => '2015-02-08 07:49:21',
            'created' => '2015-02-08 07:49:21',
        ],
    ];
}
