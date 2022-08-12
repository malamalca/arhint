<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;

/**
 * ContactsAddress Entity.
 *
 * @property string $id
 * @property string|null $contact_id
 * @property string|null $kind
 * @property string|null $street
 * @property string|null $zip
 * @property string|null $city
 * @property string|null $country_code
 * @property string|null $country
 * @property bool $primary
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class ContactsAddress extends Entity implements EntityInterface
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
