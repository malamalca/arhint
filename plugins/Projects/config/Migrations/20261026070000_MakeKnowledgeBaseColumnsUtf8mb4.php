<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

class MakeKnowledgeBaseColumnsUtf8mb4 extends BaseMigration
{
    /**
     * Older databases keep `projects` in utf8 (three bytes per character), which cannot store emoji. The knowledge
     * base uses some as markers and the AI writes others, so the description needs four-byte characters.
     * Only the description column is converted, the rest of the table is left as it is.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'mysql') {
            return;
        }

        $this->table('projects')
            ->changeColumn('descript', 'text', [
                'default' => null,
                'limit' => MysqlAdapter::TEXT_MEDIUM,
                'null' => true,
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
            ])
            ->update();

        // The table was created with the default character set of the database; make sure it is a wide one.
        $this->execute('ALTER TABLE `projects_kb_revisions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

        // Comparing ids across tables fails when their collations differ, so the id columns take the collation of
        // the columns they point to.
        $project = $this->columnCollation('projects', 'id');
        $user = $this->columnCollation('users', 'id');
        $columns = [
            'project_id' => ['NOT NULL', $project],
            'user_id' => ['NULL DEFAULT NULL', $user],
            'id' => ['NOT NULL', $project],
        ];
        foreach ($columns as $column => [$null, $collation]) {
            if ($collation === null || $collation === 'utf8mb4_unicode_ci') {
                continue;
            }
            $this->execute(sprintf(
                'ALTER TABLE `projects_kb_revisions` MODIFY `%s` CHAR(36) CHARACTER SET utf8mb4 COLLATE %s %s',
                $column,
                $collation,
                $null,
            ));
        }
    }

    /**
     * The conversion cannot be undone without losing characters, so it stays.
     *
     * @return void
     */
    public function down(): void
    {
    }

    /**
     * Collation of a column, or null when it cannot be read.
     *
     * @param string $table Table name.
     * @param string $column Column name.
     * @return string|null
     */
    private function columnCollation(string $table, string $column): ?string
    {
        try {
            $row = $this->fetchRow(sprintf(
                'SELECT collation_name FROM information_schema.columns ' .
                "WHERE table_schema = DATABASE() AND table_name = '%s' AND column_name = '%s'",
                $table,
                $column,
            ));
        } catch (Throwable) {
            return null;
        }

        return $row ? (string)($row['collation_name'] ?? $row[0] ?? '') ?: null : null;
    }
}
