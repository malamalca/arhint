<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAiProcessingToCounters extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // AI analyses only the documents of counters that have this switched on.
        $this->table('documents_counters')
            ->addColumn('ai_processing', 'boolean', ['default' => false, 'null' => false, 'after' => 'active'])
            ->update();
    }
}
