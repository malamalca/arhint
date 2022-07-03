<?php
namespace Projects\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProjectsLogsFixture
 */
class ProjectsLogsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => '0cc5e896-da92-4408-af50-9eaf1d4cc50b',
                'project_id' => '4dd53305-9715-4be4-b169-20defe113d2a',
                'user_id' => USER_ADMIN,
                'descript' => 'This is a short text.',
                'created' => '2019-04-10 07:53:24',
                'modified' => '2019-04-10 07:53:24',
            ],
        ];
        parent::init();
    }
}
