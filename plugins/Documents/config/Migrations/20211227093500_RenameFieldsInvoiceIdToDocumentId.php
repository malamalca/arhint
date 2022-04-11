<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RenameFieldsInvoiceIdToDocumentId extends AbstractMigration
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

        $table = $this->table('documents_attachments');
        $table->renameColumn('invoice_id', 'document_id')
            ->save();

        $table = $this->table('documents_clients');
        $table->renameColumn('invoice_id', 'document_id')
            ->save();

        $table = $this->table('documents_items');
        $table->renameColumn('invoice_id', 'document_id')
            ->save();

        $table = $this->table('documents_links');
        $table->renameColumn('invoice_id', 'document_id')
            ->save();

        $table = $this->table('documents_taxes');
        $table->renameColumn('invoice_id', 'document_id')
            ->save();
    }
}
