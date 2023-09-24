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
}
