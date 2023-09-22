<?php
namespace Projects\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProjectsFixture
 */
class ProjectsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'owner_id' => COMPANY_FIRST,
            'status_id' => null,
            'no' => '2022-01',
            'title' => 'First Project Title',
            'lat' => null,
            'lon' => null,
            'colorize' => null,
            'ico' => null,
            'active' => 1,
            'created' => '2018-02-21 12:51:02',
            'modified' => '2018-02-21 12:51:02',
        ],
        [
            'id' => '4dd53305-9715-4be4-b169-20defe113d2b',
            'owner_id' => COMPANY_FIRST,
            'status_id' => null,
            'no' => '2022-02',
            'title' => 'Second Project Title',
            'lat' => null,
            'lon' => null,
            'colorize' => null,
            'ico' => null,
            'active' => 1,
            'created' => '2018-02-21 12:51:02',
            'modified' => '2018-02-21 12:51:02',
        ],
    ];
}
