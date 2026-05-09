<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\ORM\Entity;

/**
 * ContactsSearchIndex Entity
 *
 * @property string $id
 * @property string $contact_id
 * @property string $owner_id
 * @property string|null $content
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Crm\Model\Entity\Contact $contact
 */
class ContactsSearchIndex extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'contact_id' => true,
        'owner_id' => true,
        'content' => true,
        'created' => true,
        'modified' => true,
        'contact' => true,
    ];
}
