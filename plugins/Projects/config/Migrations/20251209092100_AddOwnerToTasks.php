<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddOwnerToTasks extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects_tasks')
            ->addColumn('assigned_user_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'user_id',
            ])
            ->addColumn('reopened', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'descript',
            ])
            ->renameColumn('date_complete', 'closed')
            ->save();
    }
}
