<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateTableTravelOrdersMileages extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('travel_orders_mileages', ['id' => false, 'primary_key' => ['id']]);
        $table
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('travel_order_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('start_time', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('end_time', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('road_description', 'text', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('distance_km', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 1,
            ])
            ->addColumn('price_per_km', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 2,
            ])
            ->addColumn('total', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['travel_order_id'])
            ->create();
    }
}
