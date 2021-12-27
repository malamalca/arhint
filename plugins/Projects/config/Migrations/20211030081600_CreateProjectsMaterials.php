<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateProjectsMaterials extends AbstractMigration
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
        $table = $this->table('projects_materials', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('group_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('descript', 'text', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('thickness', 'decimal', [
                'default' => 0,
                'null' => false,
                'precision' => 8,
                'scale' => 1,
            ])
            ->create();
    }
}
