<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddIndexToProjects extends AbstractMigration
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
        $table = $this->table('projects');
        $table
            ->addIndex(['owner_id', 'active', 'no', 'title'], ['name' => 'IX_LIST'])
            ->update();
    }
}
