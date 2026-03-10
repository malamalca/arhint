<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddWorkflowActorsToTravelOrders extends AbstractMigration
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
            ->addColumn('entered_by_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'dat_approval',
            ])
            ->addColumn('entered_at', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'entered_by_id',
            ])
            ->addColumn('approved_by_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'entered_at',
            ])
            ->addColumn('approved_at', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'approved_by_id',
            ])
            ->addColumn('processed_by_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'approved_at',
            ])
            ->addColumn('processed_at', 'datetime', [
                'default' => null,
                'null' => true,
                'after' => 'processed_by_id',
            ])
            ->save();
    }
}
