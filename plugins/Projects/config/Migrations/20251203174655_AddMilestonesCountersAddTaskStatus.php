<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddMilestonesCountersAddTaskStatus extends AbstractMigration
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
            ->addColumn('status', 'integer', [
                'default' => 1,
                'limit' => 4,
                'null' => false,
                'after' => 'no',
            ])
            ->save();

        $this->table('projects')
            ->addColumn('milestones_open', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'active',
            ])
            ->addColumn('milestones_done', 'integer', [
                'default' => 0,
                'null' => false,
                'after' => 'milestones_open',
            ])
            ->save();
    }
}
