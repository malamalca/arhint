<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * DocumentsLink Entity.
 *
 * @property string $id
 * @property string|null $link_id
 * @property string|null $document_id
 * @property string|null $model
 * @property \Documents\Model\Entity\Invoice|null $invoice
 * @property \Documents\Model\Entity\Document|null $document
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class DocumentsLink extends Entity
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
     * The document on the other end of the link, together with the controller that shows it.
     *
     * Returns null when the linked document is of a kind that is not loaded (e.g. a travel order)
     * or no longer exists.
     *
     * @return array{controller: string, document: \Documents\Model\Entity\Invoice|\Documents\Model\Entity\Document}|null
     */
    public function linkedDocument(): ?array
    {
        if ($this->model === 'Invoice' && $this->invoice !== null) {
            return ['controller' => 'Invoices', 'document' => $this->invoice];
        }
        if ($this->model === 'Document' && $this->document !== null) {
            return ['controller' => 'Documents', 'document' => $this->document];
        }

        return null;
    }
}
