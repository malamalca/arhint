<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddDescriptToProjectWorkhours extends AbstractMigration
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
        $table = $this->table('projects_workhours')
            ->addColumn('descript', 'text', [
                'default' => null,
                'null' => true,
                'after' => 'duration',
            ])
            ->addColumn('dat_confirmed', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'duration',
            ])
            ->update();
    }
}
