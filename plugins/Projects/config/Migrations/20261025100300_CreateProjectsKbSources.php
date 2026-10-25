<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateProjectsKbSources extends BaseMigration
{
    /**
     * Events of a project (log, authority event, task, ...) that the knowledge base has dealt with.
     *
     * The modification time is part of the key, so an edited event is analysed again. A `failed` row counts
     * the attempts and becomes `skipped` after the last one.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects_kb_sources', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('project_id', 'uuid')
            ->addColumn('source_model', 'string', ['limit' => 40])
            ->addColumn('source_id', 'string', ['limit' => 40])
            ->addColumn('source_modified', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('revision_id', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('status', 'string', ['limit' => 10])
            ->addColumn('attempts', 'integer', ['default' => 0])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addColumn('modified', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['source_model', 'source_id', 'source_modified'], ['unique' => true, 'name' => 'ux_kb_source'])
            ->addIndex(['project_id', 'status'])
            ->create();
    }
}
