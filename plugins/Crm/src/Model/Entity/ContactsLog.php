<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use App\Lib\AISerializableInterface;
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
class ContactsLog extends Entity implements AISerializableInterface
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
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'kind' => $this->kind,
            'descript' => $this->descript,
            'created' => $this->created ? (string)$this->created : null,
        ];
    }
}
