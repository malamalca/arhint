<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateProjectsAuthorities extends BaseMigration
{
    /**
     * Create the authority register and append-only procedure history.
     *
     * Foreign keys need the same character set and collation on both sides, and older databases
     * keep `projects` and `documents` in utf8 while new tables default to utf8mb4. The new tables
     * therefore take the collation of the tables they reference.
     *
     * @return void
     */
    public function up(): void
    {
        $projects = $this->tableCollation('projects');
        $documents = $this->tableCollation('documents');
        $options = fn(string $collation): array => [
            'id' => false,
            'primary_key' => ['id'],
            'encoding' => explode('_', $collation)[0],
            'collation' => $collation,
        ];

        $this->table('projects')
            ->addColumn('authority_version', 'string', ['limit' => 100, 'null' => true, 'default' => null])
            ->addColumn('authority_revision', 'uuid', ['null' => true, 'default' => null])
            ->update();

        $this->table('projects_authorities', $options($projects))
            ->addColumn('id', 'uuid')
            ->addColumn('owner_id', 'uuid')
            ->addColumn('title', 'string', ['limit' => 250])
            ->addColumn('contact', 'text', ['null' => true])
            ->addColumn('active', 'boolean', ['default' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['owner_id', 'title'])
            ->create();

        $this->table('projects_authority_cases', $options($projects))
            ->addColumn('id', 'uuid')
            ->addColumn('project_id', 'uuid')
            ->addColumn('authority_id', 'uuid')
            ->addColumn('title', 'string', ['limit' => 250])
            ->addColumn('conditions_required', 'string', ['limit' => 20, 'default' => 'unknown'])
            ->addColumn('revision', 'uuid')
            ->addColumn('opinion_required', 'string', ['limit' => 20, 'default' => 'unknown'])
            ->addColumn('conditions_reason', 'text', ['null' => true])
            ->addColumn('opinion_reason', 'text', ['null' => true])
            ->addColumn('user_id', 'uuid', ['null' => true])
            ->addColumn('reference', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('descript', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['project_id'])
            ->addIndex(['authority_id'])
            ->addForeignKey('project_id', 'projects', 'id', ['delete' => 'RESTRICT'])
            ->addForeignKey('authority_id', 'projects_authorities', 'id', ['delete' => 'RESTRICT'])
            ->create();

        $this->table('projects_authority_events', $options($projects))
            ->addColumn('id', 'uuid')
            ->addColumn('case_id', 'uuid')
            ->addColumn('user_id', 'uuid')
            ->addColumn('phase', 'string', ['limit' => 10])
            ->addColumn('kind', 'string', ['limit' => 30])
            ->addColumn('event_date', 'date')
            ->addColumn('parent_id', 'uuid', ['null' => true])
            ->addColumn('document_version', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('evidence_hash', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('date_due', 'date', ['null' => true])
            ->addColumn('descript', 'text', ['null' => true])
            ->addColumn('sequence', 'integer')
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['case_id', 'sequence'], ['unique' => true])
            ->addForeignKey('case_id', 'projects_authority_cases', 'id', ['delete' => 'RESTRICT'])
            ->addForeignKey('parent_id', 'projects_authority_events', 'id', ['delete' => 'RESTRICT'])
            ->create();

        // document_id references `documents`, the rest of the table references the events.
        $documentIdOptions = $documents === $projects ? [] : [
            'limit' => 36,
            'encoding' => explode('_', $documents)[0],
            'collation' => $documents,
        ];
        $this->table('projects_authority_event_documents', $options($projects))
            ->addColumn('id', 'uuid')
            ->addColumn('event_id', 'uuid')
            ->addColumn('document_id', $documentIdOptions === [] ? 'uuid' : 'char', $documentIdOptions)
            ->addColumn('role', 'string', ['limit' => 30])
            ->addIndex(['event_id', 'document_id', 'role'], ['unique' => true])
            ->addForeignKey('event_id', 'projects_authority_events', 'id', ['delete' => 'RESTRICT'])
            ->addForeignKey('document_id', 'documents', 'id', ['delete' => 'RESTRICT'])
            ->create();

        $this->table('projects_authority_reviews', $options($projects))
            ->addColumn('id', 'uuid')
            ->addColumn('project_id', 'uuid')
            ->addColumn('sequence', 'integer')
            ->addColumn('user_id', 'uuid')
            ->addColumn('kind', 'string', ['limit' => 20])
            ->addColumn('snapshot_hash', 'string', ['limit' => 64])
            ->addColumn('descript', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['project_id', 'kind'])
            ->addIndex(['project_id', 'sequence'], ['unique' => true])
            ->addForeignKey('project_id', 'projects', 'id', ['delete' => 'RESTRICT'])
            ->create();
    }

    /**
     * Drop the tables in reverse order of their foreign keys.
     *
     * @return void
     */
    public function down(): void
    {
        foreach (
            [
                'projects_authority_reviews',
                'projects_authority_event_documents',
                'projects_authority_events',
                'projects_authority_cases',
                'projects_authorities',
            ] as $table
        ) {
            $this->table($table)->drop()->save();
        }

        $this->table('projects')
            ->removeColumn('authority_version')
            ->removeColumn('authority_revision')
            ->update();
    }

    /**
     * Collation of an existing table in the current database.
     *
     * @param string $table Table name.
     * @return string
     */
    private function tableCollation(string $table): string
    {
        try {
            $row = $this->fetchRow(sprintf(
                'SELECT table_collation FROM information_schema.tables ' .
                "WHERE table_schema = DATABASE() AND table_name = '%s'",
                $table,
            ));
        } catch (Throwable) {
            // Not MySQL: there is no collation to match.
            return 'utf8mb4_unicode_ci';
        }

        return (string)($row['table_collation'] ?? $row[0] ?? 'utf8mb4_unicode_ci');
    }
}
