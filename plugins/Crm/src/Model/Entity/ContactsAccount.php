<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use App\Lib\AISerializableInterface;
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
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class ContactsAccount extends Entity implements EntityInterface, AISerializableInterface
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
            'kind' => $this->kind,
            'iban' => $this->iban,
            'bic' => $this->bic,
            'bank' => $this->bank,
            'primary' => $this->primary,
        ];
    }
}
