<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RenameTableDocumentsToInvoices extends AbstractMigration
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
        $table = $this->table('documents');
        $table->rename('invoices')
            ->save();

        $table = $this->table('documents_items');
        $table->rename('invoices_items')
            ->renameColumn('document_id', 'invoice_id')
            ->save();

        $table = $this->table('documents_taxes');
        $table->rename('invoices_taxes')
            ->renameColumn('document_id', 'invoice_id')
            ->save();
    }
}
