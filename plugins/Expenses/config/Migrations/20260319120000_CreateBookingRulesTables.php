<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class CreateBookingRulesTables extends AbstractMigration
{
    /**
     * Up Method.
     *
     * @return void
     */
    public function up(): void
    {
        // booking_rules
        $this->table('booking_rules', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('owner_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('model', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
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
            ->addIndex(['model'])
            ->create();

        // booking_rule_filters
        $this->table('booking_rule_filters', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('rule_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('left_bracket_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'limit' => 5,
            ])
            ->addColumn('field', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('operator', 'string', [
                'default' => null,
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('value', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('right_bracket_count', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'limit' => 5,
            ])
            ->addColumn('end_operator', 'string', [
                'default' => null,
                'limit' => 10,
                'null' => true,
            ])
            ->addColumn('sort', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'limit' => 10,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['rule_id'])
            ->create();

        // booking_rule_account_entries
        $this->table('booking_rule_account_entries', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('rule_id', 'uuid', [
                'default' => null,
                'null' => false,
            ])
            ->addColumn('account_id', 'integer', [
                'default' => null,
                'null' => false,
                'signed' => false,
                'limit' => 10,
            ])
            ->addColumn('value', 'string', [
                'default' => '',
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('sort', 'integer', [
                'default' => 0,
                'null' => false,
                'signed' => false,
                'limit' => 10,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true,
            ])
            ->addIndex(['rule_id'])
            ->addIndex(['account_id'])
            ->create();
    }

    /**
     * Down Method.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('booking_rule_account_entries')->drop()->save();
        $this->table('booking_rule_filters')->drop()->save();
        $this->table('booking_rules')->drop()->save();
    }
}
