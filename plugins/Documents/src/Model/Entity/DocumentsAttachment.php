<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * DocumentsAttachment Entity.
 *
 * @property string $id
 * @property string|null $document_id
 * @property string|null $model
 * @property string|null $filename
 * @property string|null $original
 * @property string|null $ext
 * @property string|null $mimetype
 * @property int|null $filesize
 * @property int|null $height
 * @property int|null $width
 * @property string|null $title
 * @property string|null $description
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class DocumentsAttachment extends Entity
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
