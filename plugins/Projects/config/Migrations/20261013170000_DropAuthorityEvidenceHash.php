<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class DropAuthorityEvidenceHash extends BaseMigration
{
    /**
     * A received opinion completes the procedure, so no approval digest is stored any more.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('projects_authority_events')->removeColumn('evidence_hash')->update();
    }

    /**
     * Restore the column empty; its values are not recoverable.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('projects_authority_events')
            ->addColumn('evidence_hash', 'string', ['limit' => 64, 'null' => true, 'default' => null])
            ->update();
    }
}
