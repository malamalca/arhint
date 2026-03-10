<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class FixDecimalScales extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('travel_orders_mileages')
            ->changeColumn('distance_km', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 1,
            ])
            ->changeColumn('price_per_km', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 2,
            ])
            ->update();

        $this->table('travel_orders_expenses')
            ->changeColumn('quantity', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 10,
                'scale' => 1,
            ])
            ->changeColumn('price', 'decimal', [
                'default' => null,
                'null' => true,
                'precision' => 15,
                'scale' => 2,
            ])
            ->update();
    }
}
