<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BookingOrderEntry Entity.
 *
 * @property string $id
 * @property string $booking_order_id
 * @property int $account_id
 * @property string|null $partner_id
 * @property string|null $model
 * @property string|null $foreign_id
 * @property int $no
 * @property string|null $descript
 * @property \Cake\Database\Type\DecimalType $debit
 * @property \Cake\Database\Type\DecimalType $credit
 *
 * @property \Expenses\Model\Entity\BookingOrder $booking_order
 * @property \Expenses\Model\Entity\Account $account
 * @property \Expenses\Model\Entity\Partner|null $partner
 */
class BookingOrderEntry extends Entity
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
