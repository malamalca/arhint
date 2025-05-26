<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\ORM\Entity;

/**
 * AdremasContact Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $adrema_id
 * @property string|null $contact_id
 * @property string|null $contacts_address_id
 * @property string|null $contacts_email_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \Crm\Model\Entity\ContactsAddress|null $address
 * @property \Crm\Model\Entity\ContactsEmail|null $email
 */
class AdremasContact extends Entity
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
