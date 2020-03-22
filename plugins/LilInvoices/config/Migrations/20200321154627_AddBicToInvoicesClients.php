<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddBicToInvoicesClients extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function change()
    {
        $table = $this->table('invoices_clients');
        $table->addColumn('bic', 'string', [
            'default' => null,
            'limit' => 11,
            'null' => true,
            'after' => 'iban'
        ]);
        $table->update();
    }
}
