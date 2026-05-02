<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateBankStatementsTables extends AbstractMigration
{
    public function up(): void
    {
        // bank_statements
        $this->table('bank_statements', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => null, 'null' => false])
            ->addColumn('owner_id', 'uuid', ['default' => null, 'null' => false, 'comment' => 'Company (owner) id'])
            ->addColumn('user_id', 'uuid', ['default' => null, 'null' => false, 'comment' => 'User who imported the statement'])
            ->addColumn('no', 'string', ['default' => null, 'limit' => 100, 'null' => false, 'comment' => 'Statement ID'])
            ->addColumn('kind', 'string', ['default' => null, 'limit' => 50, 'null' => true, 'comment' => 'Statement type/format'])
            ->addColumn('iban', 'string', ['default' => null, 'limit' => 50, 'null' => false])
            ->addColumn('dat_issue', 'date', ['default' => null, 'null' => false, 'comment' => 'Statement date'])
            ->addColumn('currency', 'string', ['default' => 'EUR', 'limit' => 10, 'null' => false])
            ->addColumn('dat_import', 'datetime', ['default' => null, 'null' => false, 'comment' => 'Import timestamp'])
            ->addColumn('total_credit', 'decimal', ['default' => '0.00', 'precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('total_debit', 'decimal', ['default' => '0.00', 'precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('count_credit', 'integer', ['default' => 0, 'limit' => 10, 'null' => false, 'signed' => false])
            ->addColumn('count_debit', 'integer', ['default' => 0, 'limit' => 10, 'null' => false, 'signed' => false])
            ->addColumn('saldo', 'decimal', ['default' => '0.00', 'precision' => 15, 'scale' => 2, 'null' => false, 'comment' => 'Closing minus opening balance'])
            ->addIndex(['owner_id'])
            ->addIndex(['user_id'])
            ->addIndex(['iban'])
            ->create();

        // bank_statement_entries
        $this->table('bank_statement_entries', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['default' => null, 'null' => false])
            ->addColumn('statement_id', 'uuid', ['default' => null, 'null' => false])
            ->addColumn('no', 'string', ['default' => null, 'limit' => 50, 'null' => true, 'comment' => 'Bank transaction reference'])
            ->addColumn('client', 'string', ['default' => null, 'limit' => 255, 'null' => true, 'comment' => 'Counterparty name'])
            ->addColumn('descript', 'string', ['default' => null, 'limit' => 500, 'null' => true])
            ->addColumn('credit', 'decimal', ['default' => '0.00', 'precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('debit', 'decimal', ['default' => '0.00', 'precision' => 15, 'scale' => 2, 'null' => false])
            ->addColumn('iban', 'string', ['default' => null, 'limit' => 50, 'null' => true, 'comment' => 'Counterparty IBAN'])
            ->addColumn('ref', 'string', ['default' => null, 'limit' => 255, 'null' => true, 'comment' => 'Payment reference'])
            ->addColumn('dat_issue', 'date', ['default' => null, 'null' => true, 'comment' => 'Transaction booking date'])
            ->addIndex(['statement_id'])
            ->create();
    }

    public function down(): void
    {
        $this->table('bank_statement_entries')->drop()->save();
        $this->table('bank_statements')->drop()->save();
    }
}
