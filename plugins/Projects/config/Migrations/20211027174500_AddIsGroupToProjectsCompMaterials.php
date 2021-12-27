<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddIsGroupToProjectsCompMaterials extends AbstractMigration
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
        $table = $this->table('projects_comp_materials')
            ->addColumn('is_group', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'composite_id',
            ])
            ->update();
    }
}
