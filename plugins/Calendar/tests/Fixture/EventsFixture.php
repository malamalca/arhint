<?php
declare(strict_types=1);

namespace Calendar\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * EventsFixture
 */
class EventsFixture extends TestFixture
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
                'id' => '185383a4-38c8-4194-9516-52c9069bc3bf',
                'owner_id' => '679bebc9-655a-4843-a522-ec1c9f5476db',
                'user_id' => '26a56aa2-c864-4860-b4d0-0078b1d7fc1d',
                'title' => 'Lorem ipsum dolor sit amet',
                'location' => 'Lorem ipsum dolor sit amet',
                'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'all_day' => 1,
                'dat_start' => '2022-01-27 12:49:26',
                'dat_end' => '2022-01-27 12:49:26',
                'reminder' => 1,
                'created' => '2022-01-27 12:49:26',
                'modified' => '2022-01-27 12:49:26',
            ],
        ];
        parent::init();
    }
}
