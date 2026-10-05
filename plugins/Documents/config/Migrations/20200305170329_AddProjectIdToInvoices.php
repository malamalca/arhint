<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddProjectIdToInvoices extends BaseMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * @return void
     */
    public function change()
    {
        $table = $this->table('invoices');
        $table->addColumn('project_id', 'uuid', [
            'default' => null,
            'limit' => null,
            'null' => true,
            'after' => 'counter_id',
        ])
        ->update();
    }
}
