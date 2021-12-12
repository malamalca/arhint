<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddPmtModPmtRefPmtDaysToInvoicesCounters extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('invoices_counters');
        $table->addColumn('pmt_mod', 'string', [
            'default' => null,
            'limit' => 4,
            'null' => true,
            'after' => 'mask',
        ]);
        $table->addColumn('pmt_ref', 'string', [
            'default' => null,
            'limit' => 35,
            'null' => true,
            'after' => 'pmt_mod',
        ]);
        $table->addColumn('pmt_days', 'integer', [
            'default' => null,
            'limit' => 4,
            'null' => true,
            'after' => 'pmt_ref',
        ]);
        $table->update();
    }
}
