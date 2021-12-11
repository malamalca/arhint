<?php
namespace LilProjects\Test\Fixture;

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
                'project_id' => '8655c3f6-2682-4f9c-a528-0f76cfdb45e4',
                'user_id' => '378c2441-fe24-47d0-bb79-a35948ef4d28',
                'descript' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'created' => '2019-04-10 07:53:24',
                'modified' => '2019-04-10 07:53:24',
            ],
        ];
        parent::init();
    }
}
