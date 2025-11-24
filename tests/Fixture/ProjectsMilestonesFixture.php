<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProjectsMilestonesFixture
 */
class ProjectsMilestonesFixture extends TestFixture
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
                'id' => 'fc046d5a-707e-4981-9f2b-ea225688d1b4',
                'project_id' => 'c025ed8c-8526-4d43-be99-a5a675f0892c',
                'title' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'due' => '2025-11-23 17:16:47',
                'created' => '2025-11-23 17:16:47',
                'modified' => '2025-11-23 17:16:47',
            ],
        ];
        parent::init();
    }
}
