<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * LogsFixture
 */
class LogsFixture extends TestFixture
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
                'id' => '207a7922-29ec-4616-a97f-9c4fdc51100c',
                'model' => 'Lorem ipsum dolor sit amet',
                'foreign_id' => 'd40c2975-ba8d-466e-8ef4-b8de2581a129',
                'user_id' => 'a5903f19-046a-4e44-b4cf-6aa3ecbbdd17',
                'descript' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
                'created' => '2025-12-19 17:19:31',
                'modified' => '2025-12-19 17:19:31',
            ],
        ];
        parent::init();
    }
}
