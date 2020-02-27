<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddStatusIdToProjects extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('projects')
            ->addColumn('status_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'owner_id'
            ])
            ->update();
    }
}
