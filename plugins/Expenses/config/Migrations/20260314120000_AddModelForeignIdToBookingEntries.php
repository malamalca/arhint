<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class AddModelForeignIdToBookingEntries extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('booking_order_entries')
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => true,
                'after' => 'partner_id',
            ])
            ->addColumn('foreign_id', 'uuid', [
                'default' => null,
                'null' => true,
                'after' => 'model',
            ])
            ->update();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('booking_order_entries')
            ->removeColumn('model')
            ->removeColumn('foreign_id')
            ->update();
    }
}
