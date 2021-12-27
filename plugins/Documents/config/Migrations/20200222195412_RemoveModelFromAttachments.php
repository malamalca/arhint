<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RemoveModelFromAttachments extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up()
    {
        $this->execute('DELETE FROM invoices_attachments WHERE model IS NULL');

        $table = $this->table('invoices_attachments');
        $table
            ->renameColumn('foreign_id', 'invoice_id')
            ->removeColumn('model')
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down()
    {
        $table = $this->table('invoices_attachments');
        $table
            ->renameColumn('invoice_id', 'foreign_id')
            ->addColumn('model', 'string', ['after' => 'id', 'default' => 'Invoice', 'null' => false, 'limit' => 50])
            ->update();
    }
}
