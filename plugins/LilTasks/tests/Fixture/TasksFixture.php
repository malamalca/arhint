<?php
namespace LilTasks\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TasksFixture
 *
 */
class TasksFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'owner_id' => ['type' => 'string', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'model' => ['type' => 'string', 'length' => 50, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'foreign_id' => ['type' => 'string', 'length' => 36, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'title' => ['type' => 'string', 'length' => 250, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'descript' => ['type' => 'text', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'started' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'deadline' => ['type' => 'date', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'completed' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'MyISAM',
            'collation' => 'utf8_unicode_ci'
        ],
    ];
    // @codingStandardsIgnoreEnd

    /**
     * Records
     *
     * @var array
     */
    public $records = [
        [
            'id' => 'b3a635df-c8dc-4fc4-82a9-062002007571',
            'owner_id' => 'Lorem ipsum dolor sit amet',
            'model' => 'Lorem ipsum dolor sit amet',
            'foreign_id' => 'Lorem ipsum dolor sit amet',
            'title' => 'Lorem ipsum dolor sit amet',
            'descript' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
            'started' => '2015-12-07',
            'deadline' => '2015-12-07',
            'completed' => '2015-12-07 17:05:06',
            'created' => '2015-12-07 17:05:06',
            'modified' => '2015-12-07 17:05:06'
        ],
    ];
}
