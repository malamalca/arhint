<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddPrimaryToInvoicesCounters extends AbstractMigration
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
        $table->addColumn('primary', 'boolean', [
            'default' => false,
            'null' => false,
            'after' => 'tpl_footer_id',
        ]);
        $table->update();
    }
}
