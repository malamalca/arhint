<?php
namespace Projects\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProjectsUsersFixture
 */
class ProjectsUsersFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            0 => [
                'id' => '4dd53305-9715-1234-b169-20defe113d2b',
                'project_id' => '4dd53305-9715-4be4-b169-20defe113d2b',
                'user_id' => USER_COMMON,
            ],
        ];
        parent::init();
    }
}
