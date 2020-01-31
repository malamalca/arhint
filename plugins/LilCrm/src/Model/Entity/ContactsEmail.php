<?php
declare(strict_types=1);

namespace LilCrm\Model\Entity;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;

/**
 * ContactsEmail Entity.
 *
 * @property string $id
 * @property string|null $contact_id
 * @property string|null $kind
 * @property string|null $email
 * @property bool $primary
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class ContactsEmail extends Entity implements EntityInterface
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
