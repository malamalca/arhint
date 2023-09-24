<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\Datasource\EntityInterface;
use Cake\ORM\Entity;

/**
 * ContactsPhone Entity.
 *
 * @property string $id
 * @property string|null $contact_id
 * @property string|null $kind
 * @property string|null $no
 * @property bool $primary
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class ContactsPhone extends Entity implements EntityInterface
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
