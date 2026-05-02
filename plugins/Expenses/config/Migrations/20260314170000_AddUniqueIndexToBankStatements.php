<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddUniqueIndexToBankStatements extends AbstractMigration
{
    public function up(): void
    {
        $this->table('bank_statements')
            ->addIndex(['owner_id', 'no'], ['unique' => true, 'name' => 'UNIQUE_OWNER_NO'])
            ->update();
    }

    public function down(): void
    {
        $this->table('bank_statements')
            ->removeIndexByName('UNIQUE_OWNER_NO')
            ->update();
    }
}
