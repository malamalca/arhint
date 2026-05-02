<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddBankStatementToBookingOrders extends AbstractMigration
{
    /**
     * Up Method.
     *
     * Adds model + foreign_id to booking_orders so that an order can be linked
     * to any source document (BankStatements, Invoices, TravelOrders, …)
     * using the same polymorphic pattern already used by booking_order_entries.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('booking_orders')
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'after' => 'no',
            ])
            ->addColumn('foreign_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'model',
            ])
            ->addIndex(['model', 'foreign_id'])
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('booking_orders')
            ->removeColumn('model')
            ->removeColumn('foreign_id')
            ->update();
    }
}
