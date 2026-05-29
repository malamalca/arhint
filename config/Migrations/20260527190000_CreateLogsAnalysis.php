<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateLogsAnalysis extends AbstractMigration
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
        $table = $this->table('logs_analysis', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('event_id', 'uuid', [
                'null' => false,
            ])
            ->addColumn('summary', 'text', [
                'null' => true,
            ])
            ->addColumn('risks', 'text', [
                'null' => true,
            ])
            ->addColumn('blockers', 'text', [
                'null' => true,
            ])
            ->addColumn('priority', 'integer', [
                'limit' => 4,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->create();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('logs_analysis')
            ->drop()
            ->update();
    }
}