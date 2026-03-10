<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddStatusToTravelOrders extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('travel_orders');
        $table
            ->addColumn('status', 'string', [
                'default' => 'waiting_approval',
                'limit' => 30,
                'null' => false,
                'after' => 'counter_id',
            ])
            ->addColumn('dat_approval', 'date', [
                'default' => null,
                'null' => true,
                'after' => 'status',
            ])
            ->save();
    }
}
