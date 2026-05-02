<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateBookingTables extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // partners
        $this->table('partners', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('contact_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('role', 'string', [
                'default' => 'buyer',
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('date_start', 'date', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('date_end', 'date', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['contact_id'])
            ->create();

        // booking_orders
        $this->table('booking_orders', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'null' => false,
                'comment' => 'Company (owner) id',
            ])
            ->addColumn('opener_id', 'uuid', [
                'default' => null,
                'null' => false,
                'comment' => 'User who created the order',
            ])
            ->addColumn('no', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('date_created', 'date', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'default' => 'draft',
                'limit' => 10,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['owner_id'])
            ->addIndex(['opener_id'])
            ->addIndex(['status'])
            ->create();

        // booking_order_entries
        $this->table('booking_order_entries', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('booking_order_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('account_id', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('partner_id', 'uuid', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('no', 'integer', [
                'default' => null,
                'limit' => 10,
                'null' => false,
                'signed' => false,
                'comment' => 'Sequence number within the order',
            ])
            ->addColumn('descript', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('debit', 'decimal', [
                'default' => '0.00',
                'precision' => 15,
                'scale' => 2,
                'null' => false,
            ])
            ->addColumn('credit', 'decimal', [
                'default' => '0.00',
                'precision' => 15,
                'scale' => 2,
                'null' => false,
            ])
            ->addIndex(['booking_order_id'])
            ->addIndex(['account_id'])
            ->addIndex(['partner_id'])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('booking_order_entries')->drop()->save();
        $this->table('booking_orders')->drop()->save();
        $this->table('partners')->drop()->save();
    }
}
