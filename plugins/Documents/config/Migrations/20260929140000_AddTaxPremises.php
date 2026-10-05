<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddTaxPremises extends BaseMigration
{
    public function change(): void
    {
        $this->table('documents_tax_premises', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('owner_id', 'uuid', ['null' => true])
            ->addColumn('no', 'char', ['limit' => 20, 'null' => true])
            ->addColumn('title', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('kind', 'char', ['limit' => 2, 'default' => 'RL', 'comment' => 'ReaLestate or MOvable'])
            ->addColumn('casadral_number', 'char', ['limit' => 4, 'null' => true])
            ->addColumn('building_number', 'char', ['limit' => 5, 'null' => true])
            ->addColumn('building_section_number', 'char', ['limit' => 4, 'null' => true])
            ->addColumn('street', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('house_number', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('house_number_additional', 'string', ['limit' => 10, 'null' => true])
            ->addColumn('community', 'integer', ['null' => true])
            ->addColumn('city', 'integer', ['null' => true])
            ->addColumn('postal_code', 'char', ['limit' => 4, 'null' => true])
            ->addColumn('mo_type', 'char', ['limit' => 1, 'null' => true])
            ->addColumn('validity_date', 'date', ['null' => true])
            ->addColumn('closed', 'boolean', ['default' => false])
            ->addColumn('sw_taxno', 'string', ['limit' => 8, 'null' => true])
            ->addColumn('sw_title', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['owner_id'])
            ->create();
    }
}
