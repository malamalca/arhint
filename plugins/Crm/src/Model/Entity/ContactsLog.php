<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContactsLog Entity.
 *
 * @property string $id
 * @property string|null $user_id
 * @property string|null $contact_id
 * @property string|null $kind
 * @property string|null $descript
 * @property string|null $email_uid
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class ContactsLog extends Entity
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
