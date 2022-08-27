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
 * @property string|null $contacts_address_id
 * @property string|null $title
 * @property string|null $street
 * @property string|null $city
 * @property string|null $country
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 * @property array|null $contacts_address
 */
class AdremasContact extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
