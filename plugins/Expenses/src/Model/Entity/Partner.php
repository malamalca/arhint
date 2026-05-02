<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * Partner Entity.
 *
 * @property string $id
 * @property string $contact_id
 * @property string $role
 * @property \Cake\I18n\Date|null $date_start
 * @property \Cake\I18n\Date|null $date_end
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Crm\Model\Entity\Contact $contact
 * @property \Expenses\Model\Entity\BookingOrderEntry[] $booking_order_entries
 */
class Partner extends Entity
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
