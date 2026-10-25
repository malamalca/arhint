<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

class CreateProjectsKbRevisions extends BaseMigration
{
    /**
     * Every state of a project's knowledge base. `user_id` is empty for changes made by the batch run.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects_kb_revisions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('project_id', 'uuid')
            ->addColumn('user_id', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('version', 'integer')
            ->addColumn('source', 'string', ['limit' => 20])
            ->addColumn('summary', 'string', ['limit' => 500, 'null' => true, 'default' => null])
            ->addColumn('operations', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('descript', 'text', [
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['project_id', 'version'], ['unique' => true])
            ->addIndex(['user_id'])
            ->create();
    }
}
