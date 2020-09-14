<?php
namespace LilTasks\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * TasksFoldersFixture
 *
 */
class TasksFoldersFixture extends TestFixture
{

    /**
     * Fields
     *
     * @var array
     */
    // @codingStandardsIgnoreStart
    public $fields = [
        'id' => ['type' => 'uuid', 'length' => null, 'null' => false, 'default' => null, 'comment' => '', 'precision' => null],
        'owner_id' => ['type' => 'uuid', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'title' => ['type' => 'string', 'length' => 255, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null, 'fixed' => null],
        'created' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        'modified' => ['type' => 'datetime', 'length' => null, 'null' => true, 'default' => null, 'comment' => '', 'precision' => null],
        '_constraints' => [
            'primary' => ['type' => 'primary', 'columns' => ['id'], 'length' => []],
        ],
        '_options' => [
            'engine' => 'InnoDB',
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
            'id' => '6a4b457b-0394-4b5b-a4b9-d0f602c4d98f',
            'owner_id' => 'e1162fa9-70f7-45cd-a0c5-8d8d6f47c6f2',
            'title' => 'Lorem ipsum dolor sit amet',
            'created' => '2015-12-07 18:47:12',
            'modified' => '2015-12-07 18:47:12'
        ],
    ];
}
