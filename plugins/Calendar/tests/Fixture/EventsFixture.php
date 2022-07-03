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
                'owner_id' => COMPANY_FIRST,
                'calendar_id' => USER_ADMIN,
                'title' => 'Meeting About Arhint',
                'location' => 'Ljubljana',
                'body' => 'This is an event description.',
                'all_day' => 0,
                'dat_start' => '2022-01-27 12:49:26',
                'dat_end' => '2022-01-27 13:49:26',
                'reminder' => 0,
                'created' => '2022-01-27 12:49:26',
                'modified' => '2022-01-27 12:49:26',
            ],
        ];
        parent::init();
    }
}
