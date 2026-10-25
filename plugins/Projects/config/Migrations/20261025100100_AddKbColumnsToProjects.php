<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddKbColumnsToProjects extends BaseMigration
{
    /**
     * Knowledge base bookkeeping of a project.
     *
     * - kb_version: optimistic lock, incremented by every write of the knowledge base;
     * - kb_synced: events up to this time were processed by the batch run;
     * - kb_format: 0 = free-form description, 1 = knowledge base v1.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects')
            ->addColumn('kb_version', 'integer', ['default' => 0, 'null' => false])
            ->addColumn('kb_synced', 'datetime', ['default' => null, 'null' => true])
            ->addColumn('kb_format', 'tinyinteger', ['default' => 0, 'null' => false])
            ->update();
    }
}
