<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\ORM\Entity;

/**
 * InvoicesTax Entity.
 *
 * @property string $id
 * @property string|null $invoice_id
 * @property string|null $vat_id
 * @property string|null $vat_title
 * @property float|null $vat_percent
 * @property float|null $base
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class InvoicesTax extends Entity implements AISerializableInterface
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
            'vat_title' => $this->vat_title,
            'vat_percent' => $this->vat_percent,
            'base' => $this->base,
        ];
    }
}
