<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddUserToTasks extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('tasks')
            ->addColumn('user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'folder_id',
            ])
            ->addColumn('tasker_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'user_id',
            ])
            ->addIndex(['user_id', 'created'], ['name' => 'IX_USER'])
            ->update();
    }
}
