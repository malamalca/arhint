<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;

/**
 * ContactsAccount Entity.
 *
 * @property string $id
 * @property string|null $contact_id
 * @property string|null $kind
 * @property string|null $iban
 * @property string|null $bic
 * @property string|null $bank
 * @property bool $primary
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class ContactsAccount extends Entity implements EntityInterface
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<bool>
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
