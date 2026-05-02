<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BookingOrder Entity.
 *
 * @property string $id
 * @property string $owner_id
 * @property string $opener_id
 * @property string $no
 * @property string|null $model
 * @property string|null $foreign_id
 * @property string $title
 * @property \Cake\I18n\Date $date_created
 * @property string $status
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\User $owner
 * @property \App\Model\Entity\User $opener
 * @property \Expenses\Model\Entity\BookingOrderEntry[] $booking_order_entries
 */
class BookingOrder extends Entity
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_POSTED = 'posted';
    public const STATUS_LOCKED = 'locked';

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
     * Returns a human-readable string representation of the booking order.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->no . ' - ' . $this->title;
    }

    /**
     * Returns a human-readable map of status keys to labels.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_DRAFT => __d('expenses', 'Draft'),
            self::STATUS_POSTED => __d('expenses', 'Posted'),
            self::STATUS_LOCKED => __d('expenses', 'Locked'),
        ];
    }
}
