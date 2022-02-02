<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateEvents extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('events', ['id' => false, 'primary_key' => ['id']])
        ->addColumn('id', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ])
        ->addColumn('calendar_id', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('title', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => true,
        ])
        ->addColumn('location', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => true,
        ])
        ->addColumn('body', 'text', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('all_day', 'boolean', [
            'default' => false,
            'limit' => null,
            'null' => false,
        ])
        ->addColumn('dat_start', 'datetime', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('dat_end', 'datetime', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('reminder', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('created', 'datetime', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ])
        ->addColumn('modified', 'datetime', [
            'default' => null,
            'limit' => null,
            'null' => true,
        ]);
        $table->create();
    }
}
