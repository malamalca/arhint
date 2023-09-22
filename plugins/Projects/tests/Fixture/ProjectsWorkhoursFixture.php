<?php
namespace Projects\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProjectsWorkhoursFixture
 */
class ProjectsWorkhoursFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'a1895b24-5809-40cb-9670-302a37aa35bf',
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
            'user_id' => USER_ADMIN,
            'started' => '2018-02-27 06:33:56',
            'duration' => 4 * 60 * 60,
            'created' => '2018-02-27 06:33:56',
            'modified' => '2018-02-27 06:33:56',
        ],
    ];
}
