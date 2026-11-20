<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddSignedEslog20ToInvoices extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // eSlog 2.0 document with its own signature (the `signed` column holds the signed eSlog 1.6).
        $this->table('invoices')
            ->addColumn('signed_eslog20', 'text', ['default' => null, 'limit' => null, 'null' => true, 'after' => 'signed'])
            ->update();
    }
}
