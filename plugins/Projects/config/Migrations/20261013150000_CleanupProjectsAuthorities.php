<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CleanupProjectsAuthorities extends BaseMigration
{
    /**
     * Drop columns of the discarded phase/version/requirement model and add the explicit exemption.
     *
     * A case with an `exempt_reason` is excluded from the readiness review.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('projects_authority_cases')
            ->removeColumn('conditions_required')
            ->removeColumn('opinion_required')
            ->removeColumn('conditions_reason')
            ->removeColumn('opinion_reason')
            ->addColumn('exempt_reason', 'text', ['null' => true, 'default' => null])
            ->addIndex(['user_id'])
            ->update();

        $this->table('projects_authority_events')
            ->removeColumn('phase')
            ->removeColumn('document_version')
            ->addIndex(['user_id'])
            ->update();

        $this->table('projects')
            ->removeColumn('authority_version')
            ->removeColumn('authority_revision')
            ->update();
    }

    /**
     * Restore the removed columns empty; their values are not recoverable.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('projects')
            ->addColumn('authority_version', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('authority_revision', 'uuid', ['null' => true, 'default' => null])
            ->update();

        $this->table('projects_authority_events')
            ->removeIndex(['user_id'])
            ->addColumn('phase', 'string', ['limit' => 10, 'default' => 'ALL'])
            ->addColumn('document_version', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->update();

        $this->table('projects_authority_cases')
            ->removeIndex(['user_id'])
            ->removeColumn('exempt_reason')
            ->addColumn('conditions_required', 'string', ['limit' => 20, 'default' => 'unknown'])
            ->addColumn('opinion_required', 'string', ['limit' => 20, 'default' => 'unknown'])
            ->addColumn('conditions_reason', 'text', ['null' => true])
            ->addColumn('opinion_reason', 'text', ['null' => true])
            ->update();
    }
}
