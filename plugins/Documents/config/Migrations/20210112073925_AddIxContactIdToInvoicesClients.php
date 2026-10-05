<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddIxContactIdToInvoicesClients extends BaseMigration
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
        $table = $this->table('invoices_clients');
        $table->addIndex(['contact_id'], ['name' => 'IX_CONTACT']);

        $table->update();
    }
}
