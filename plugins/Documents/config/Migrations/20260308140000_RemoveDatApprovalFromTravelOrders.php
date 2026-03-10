<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class RemoveDatApprovalFromTravelOrders extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('travel_orders')
            ->removeColumn('dat_approval')
            ->save();
    }
}
