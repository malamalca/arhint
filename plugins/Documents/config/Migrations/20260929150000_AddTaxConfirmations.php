<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTaxConfirmations extends BaseMigration
{
    /**
     * Up
     *
     * @return void
     */
    public function up(): void
    {
        // FURS expects community and city as text (max 100 chars).
        $this->table('documents_tax_premises')
            ->changeColumn('community', 'string', ['limit' => 100, 'null' => true])
            ->changeColumn('city', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('active', 'boolean', ['default' => false, 'after' => 'sw_title'])
            ->addColumn('last_request', 'text', ['null' => true, 'after' => 'active'])
            ->addColumn('last_response', 'text', ['null' => true, 'after' => 'last_request'])
            ->update();

        $this->table('documents_counters')
            ->addColumn('tax_confirmation', 'boolean', ['default' => false])
            ->addColumn('tax_premise_id', 'uuid', ['null' => true])
            ->addColumn('device_no', 'char', ['limit' => 20, 'null' => true])
            ->update();

        $this->table('invoices_tax_confirmations', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('invoice_id', 'uuid')
            ->addColumn('user_id', 'uuid', ['null' => true])
            ->addColumn('bp_no', 'char', ['limit' => 20, 'null' => true])
            ->addColumn('device_no', 'char', ['limit' => 20, 'null' => true])
            ->addColumn('issuer_taxno', 'char', ['limit' => 8, 'null' => true])
            ->addColumn('operator_taxno', 'char', ['limit' => 8, 'null' => true])
            ->addColumn('issued_at', 'datetime', ['null' => true])
            ->addColumn('zoi', 'char', ['limit' => 32, 'null' => true])
            ->addColumn('qr', 'char', ['limit' => 60, 'null' => true])
            ->addColumn('eor', 'char', ['limit' => 40, 'null' => true])
            ->addColumn('error_code', 'string', ['limit' => 40, 'null' => true])
            ->addColumn('error_message', 'text', ['null' => true])
            ->addColumn('last_request', 'text', ['null' => true])
            ->addColumn('last_response', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['invoice_id'], ['unique' => true])
            ->create();

        // Per-user FURS signing certificate (p12 + password), so it is also usable outside a browser session.
        $this->table('documents_tax_certificates', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('user_id', 'uuid')
            ->addColumn('tax_no', 'char', ['limit' => 8, 'null' => true])
            ->addColumn('p12', 'text', ['null' => true, 'comment' => 'base64 encoded p12'])
            ->addColumn('password', 'text', ['null' => true, 'comment' => 'encrypted'])
            ->addColumn('valid_to', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['user_id'], ['unique' => true])
            ->create();
    }

    /**
     * Down
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('documents_tax_certificates')->drop()->save();
        $this->table('invoices_tax_confirmations')->drop()->save();

        $this->table('documents_counters')
            ->removeColumn('tax_confirmation')
            ->removeColumn('tax_premise_id')
            ->removeColumn('device_no')
            ->update();

        $this->table('documents_tax_premises')
            ->removeColumn('active')
            ->removeColumn('last_request')
            ->removeColumn('last_response')
            ->changeColumn('community', 'integer', ['null' => true])
            ->changeColumn('city', 'integer', ['null' => true])
            ->update();
    }
}
