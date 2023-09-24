<?php
declare(strict_types=1);

namespace Tasks\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class TasksFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => 'b3a635df-c8dc-4fc4-82a9-062002007571',
            'owner_id' => COMPANY_FIRST,
            'folder_id' => '6a4b457b-0394-4b5b-a4b9-d0f602c4d98f',
            'user_id' => USER_ADMIN,
            'tasker_id' => USER_ADMIN,
            'model' => null,
            'foreign_id' => null,
            'title' => 'Bring Milk',
            'descript' => 'This is a simple Task',
            'started' => '2015-12-07',
            'deadline' => '2015-12-07',
            'completed' => null,
            'priority' => 1,
            'created' => '2015-12-07 17:05:06',
            'modified' => '2015-12-07 17:05:06',
        ],
    ];
}
