<?php
declare(strict_types=1);

namespace LilCrm\Model\Entity;

use Cake\ORM\Entity;

/**
 * Contact Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $kind
 * @property string|null $name
 * @property string|null $surname
 * @property string|null $title
 * @property string|null $descript
 * @property string|null $mat_no
 * @property string|null $tax_no
 * @property string|null $company_id
 * @property string|null $job
 * @property bool $syncable
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \LilCrm\Model\Entity\Contact $company
 * @property \LilCrm\Model\Entity\ContactsAccount|null $primary_account
 * @property \LilCrm\Model\Entity\ContactsAddress|null $primary_address
 * @property \LilCrm\Model\Entity\ContactsEmail|null $primary_email
 * @property \LilCrm\Model\Entity\ContactsPhone|null $primary_phone
 *
 * @property \LilCrm\Model\Entity\ContactsAccount $contact_accounts
 * @property \LilCrm\Model\Entity\ContactsAddress $contact_addresses
 * @property \LilCrm\Model\Entity\ContactsEmail $contact_emails
 * @property \LilCrm\Model\Entity\ContactsPhone $contact_phones
 */
class Contact extends Entity
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
