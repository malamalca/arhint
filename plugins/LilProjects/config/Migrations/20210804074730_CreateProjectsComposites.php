<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateProjectsComposites extends AbstractMigration
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
        $table = $this->table('projects_composites', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('project_id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('no', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->create();
        $table = $this->table('projects_comp_materials', ['id' => false, 'primary_key' => ['id']]);
            $table
                ->addColumn('id', 'uuid', [
                    'default' => null,
                    'limit' => null,
                    'null' => false,
                ])
                ->addColumn('composite_id', 'uuid', [
                    'default' => null,
                    'limit' => null,
                    'null' => true,
                ])
                ->addColumn('sort_order', 'integer', [
                    'default' => 0,
                    'null' => false,
                ])
                ->addColumn('descript', 'string', [
                    'default' => null,
                    'limit' => 255,
                    'null' => true,
                ])
                ->addColumn('thickness', 'decimal', [
                    'default' => null,
                    'null' => true,
                    'precision' => 8,
                    'scale' => 1,
                ])
                ->create();
    }
}
