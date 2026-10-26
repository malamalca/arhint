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
    }

    /**
     * The conversion cannot be undone without losing characters, so it stays.
     *
     * @return void
     */
    public function down(): void
    {
    }
}
