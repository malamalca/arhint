<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateAccounts extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('accounts', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
                'identity' => true,
            ])
            ->addColumn('parent_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('code', 'string', [
                'default' => null,
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('lft', 'integer', [
                'default' => 0,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('rght', 'integer', [
                'default' => 0,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('level', 'integer', [
                'default' => 0,
                'limit' => 5,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['lft', 'rght'], ['name' => 'idx_accounts_lft_rght'])
            ->addIndex(['parent_id'], ['name' => 'idx_accounts_parent_id'])
            ->addIndex(['code'], ['name' => 'idx_accounts_code'])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('accounts')->drop()->save();
    }
}
