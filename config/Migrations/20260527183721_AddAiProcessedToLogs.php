<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddAiProcessedToLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/4/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('logs')
            ->addColumn('ai_processed', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'descript',
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('logs')
            ->removeColumn('ai_processed')
            ->update();
    }
}
