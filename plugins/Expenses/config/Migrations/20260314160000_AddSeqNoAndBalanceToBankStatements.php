<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddSeqNoAndBalanceToBankStatements extends AbstractMigration
{
    public function up(): void
    {
        $this->table('bank_statements')
            ->addColumn('seq_no', 'integer', [
                'default' => null,
                'null' => true,
                'signed' => false,
                'comment' => 'Legal sequence number (LglSeqNb)',
                'after' => 'no',
            ])
            ->addColumn('balance', 'decimal', [
                'default' => null,
                'precision' => 15,
                'scale' => 2,
                'null' => true,
                'comment' => 'Opening balance (OPBD)',
                'after' => 'saldo',
            ])
            ->update();
    }

    public function down(): void
    {
        $this->table('bank_statements')
            ->removeColumn('seq_no')
            ->removeColumn('balance')
            ->update();
    }
}
