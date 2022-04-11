<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RemoveLayoutAndLayoutTitleFromInvoicesCounters extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('invoices_counters');
        $table->removeColumn('layout_title');
        $table->removeColumn('layout');
        $table->update();
    }
}
