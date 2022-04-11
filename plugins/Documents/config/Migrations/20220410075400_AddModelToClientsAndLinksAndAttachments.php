<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddModelToClientsAndLinksAndAttachments extends AbstractMigration
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
        $table
            ->addColumn('model', 'string', [
                'default' => 'Invoice',
                'limit' => 20,
                'null' => false,
                'after' => 'document_id',
            ])
            ->save();

        $table = $this->table('documents_clients');
        $table
            ->addColumn('model', 'string', [
                'default' => 'Invoice',
                'limit' => 20,
                'null' => false,
                'after' => 'contact_id',
            ])
            ->save();

        $table = $this->table('documents_links');
        $table
            ->addColumn('model', 'string', [
                'default' => 'Invoice',
                'limit' => 20,
                'null' => false,
                'after' => 'document_id',
            ])
            ->save();
    }
}
