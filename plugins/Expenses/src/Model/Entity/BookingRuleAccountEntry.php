<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BookingRuleAccountEntry Entity.
 *
 * @property string $id
 * @property string $rule_id
 * @property int $account_id
 * @property string $value  Expression or field name yielding the monetary amount (e.g. 'net_total', '0', 'total')
 * @property int $sort
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Expenses\Model\Entity\BookingRule $booking_rule
 * @property \Expenses\Model\Entity\Account $account
 */
class BookingRuleAccountEntry extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
