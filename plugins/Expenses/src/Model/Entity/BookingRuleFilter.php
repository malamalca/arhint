<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BookingRuleFilter Entity.
 *
 * @property string $id
 * @property string $rule_id
 * @property int $left_bracket_count
 * @property string $field
 * @property string $operator
 * @property string $value
 * @property int $right_bracket_count
 * @property string|null $end_operator  null | 'and' | 'or'
 * @property int $sort
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Expenses\Model\Entity\BookingRule $booking_rule
 */
class BookingRuleFilter extends Entity
{
    /** @var array<string> Supported operator names */
    public const OPERATORS = [
        'isEqual',
        'isNotEqual',
        'startsWith',
        'endsWith',
        'contains',
        'notContains',
        'isGreaterThan',
        'isLessThan',
        'isEmpty',
        'isNotEmpty',
    ];

    /** @var array<string> Supported end operators */
    public const END_OPERATORS = ['and', 'or'];

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
     * Returns a human-readable map of operator keys to labels.
     *
     * @return array<string, string>
     */
    public static function operatorLabels(): array
    {
        return [
            'isEqual' => __d('expenses', 'equals'),
            'isNotEqual' => __d('expenses', 'not equals'),
            'startsWith' => __d('expenses', 'starts with'),
            'endsWith' => __d('expenses', 'ends with'),
            'contains' => __d('expenses', 'contains'),
            'notContains' => __d('expenses', 'not contains'),
            'isGreaterThan' => __d('expenses', 'greater than'),
            'isLessThan' => __d('expenses', 'less than'),
            'isEmpty' => __d('expenses', 'is empty'),
            'isNotEmpty' => __d('expenses', 'is not empty'),
        ];
    }
}
