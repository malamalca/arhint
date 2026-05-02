<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BookingRule Entity.
 *
 * @property string $id
 * @property string $owner_id
 * @property string $model
 * @property string $title
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Expenses\Model\Entity\BookingRuleFilter[] $booking_rule_filters
 * @property \Expenses\Model\Entity\BookingRuleAccountEntry[] $booking_rule_account_entries
 */
class BookingRule extends Entity
{
    /** @var array<string> Supported model names */
    public const MODELS = ['Invoices', 'Documents', 'TravelOrders', 'BankStatements'];

    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Returns a human-readable map of model keys to labels.
     *
     * @return array<string, string>
     */
    public static function modelLabels(): array
    {
        return [
            'Invoices' => __d('expenses', 'Invoices'),
            'Documents' => __d('expenses', 'Documents'),
            'TravelOrders' => __d('expenses', 'Travel Orders'),
            'BankStatements' => __d('expenses', 'Bank Statements'),
        ];
    }
}
