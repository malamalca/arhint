<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class RemoveDatApprovalFromTravelOrders extends BaseMigration
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
