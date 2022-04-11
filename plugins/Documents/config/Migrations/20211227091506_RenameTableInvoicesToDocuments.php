<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RenameTableInvoicesToDocuments extends AbstractMigration
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
        $table = $this->table('invoices');
        $table->rename('documents')
            ->save();

        $table = $this->table('invoices_attachments');
        $table->rename('documents_attachments')
            ->save();

        $table = $this->table('invoices_clients');
        $table->rename('documents_clients')
            ->save();

        $table = $this->table('invoices_counters');
        $table->rename('documents_counters')
            ->save();

        $table = $this->table('invoices_counters_users');
        $table->rename('documents_counters_users')
            ->save();

        $table = $this->table('invoices_items');
        $table->rename('documents_items')
            ->save();

        $table = $this->table('invoices_links');
        $table->rename('documents_links')
            ->save();

        $table = $this->table('invoices_taxes');
        $table->rename('documents_taxes')
            ->save();

        $table = $this->table('invoices_templates');
        $table->rename('documents_templates')
            ->save();
    }
}
