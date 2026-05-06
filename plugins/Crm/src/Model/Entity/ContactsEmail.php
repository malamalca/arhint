<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use App\Lib\AISerializableInterface;
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
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class ContactsEmail extends Entity implements EntityInterface, AISerializableInterface
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
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->email;
    }

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'email' => $this->email,
            'kind' => $this->kind,
            'primary' => $this->primary,
        ];
    }
}
