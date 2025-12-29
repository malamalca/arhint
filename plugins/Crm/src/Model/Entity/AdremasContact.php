<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\ORM\Entity;
use JsonException;

/**
 * AdremasContact Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $adrema_id
 * @property string|null $contact_id
 * @property string|null $contacts_address_id
 * @property string|null $contacts_email_id
 * @property string|null $descript
 * @property array|null $user_data
 * @property array|null $data
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \Crm\Model\Entity\Contact|null $contact
 * @property \Crm\Model\Entity\ContactsAddress|null $contacts_address
 * @property \Crm\Model\Entity\ContactsEmail|null $contacts_email
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

    /**
     * Decode user values JSON
     *
     * @return array<string, mixed>|null Decoded values or empty string on failure
     */
    protected function _getUserData(): ?array
    {
        if (empty($this->descript)) {
            return null;
        }
        try {
            return json_decode($this->descript, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }
    }
}
