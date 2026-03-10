<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddVehicleTitleToTravelOrders extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('travel_orders');
        if (!$table->hasColumn('vehicle_title')) {
            $table
                ->addColumn('vehicle_title', 'string', [
                    'default' => null,
                    'limit' => 200,
                    'null' => true,
                    'after' => 'vehicle_owner',
                ])
                ->update();
        }
    }
}
