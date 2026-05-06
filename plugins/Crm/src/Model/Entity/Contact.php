<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use App\Lib\AISerializableInterface;
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
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Crm\Model\Entity\Contact $company
 * @property \Crm\Model\Entity\ContactsAccount|null $primary_account
 * @property \Crm\Model\Entity\ContactsAddress|null $primary_address
 * @property \Crm\Model\Entity\ContactsEmail|null $primary_email
 * @property \Crm\Model\Entity\ContactsPhone|null $primary_phone
 *
 * @property array $contacts_accounts
 * @property array $contacts_addresses
 * @property array $contacts_emails
 * @property array $contacts_phones
 */
class Contact extends Entity implements AISerializableInterface
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
     * String representation of the contact, used in dropdowns and similar places.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->title ?? '';
    }

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'name' => $this->name,
            'surname' => $this->surname,
            'title' => $this->title,
            'mat_no' => $this->mat_no,
            'tax_no' => $this->tax_no,
            'job' => $this->job,
            'company_id' => $this->company_id,
            'emails' => array_map(fn($e) => $e->toAIArray(), $this->contacts_emails ?? []),
            'phones' => array_map(fn($e) => $e->toAIArray(), $this->contacts_phones ?? []),
            'addresses' => array_map(fn($e) => $e->toAIArray(), $this->contacts_addresses ?? []),
            'accounts' => array_map(fn($e) => $e->toAIArray(), $this->contacts_accounts ?? []),
        ];
    }
}
