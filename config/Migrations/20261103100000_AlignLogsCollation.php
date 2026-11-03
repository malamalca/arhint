<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlignLogsCollation extends BaseMigration
{
    /**
     * `logs` and `logs_analysis` were created with the default collation of the database connection, while the
     * tables they are joined with (`projects`, `users`, `events`, ...) use another one. Comparing ids across
     * tables then fails with "Illegal mix of collations", so both tables take the collation of `projects`.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->getAdapter()->getAdapterType() !== 'mysql') {
            return;
        }

        $collation = $this->columnCollation('projects', 'id');
        if ($collation === null) {
            return;
        }
        $charset = explode('_', $collation)[0];

        foreach (['logs', 'logs_analysis'] as $table) {
            if ($this->columnCollation($table, 'id') === $collation) {
                continue;
            }
            $this->execute(sprintf(
                'ALTER TABLE `%s` CONVERT TO CHARACTER SET %s COLLATE %s',
                $table,
                $charset,
                $collation,
            ));
        }
    }

    /**
     * The previous collation depended on the database defaults, so there is nothing sensible to go back to.
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
