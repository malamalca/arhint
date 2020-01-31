<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoicesAttachment Entity.
 *
 * @property string $id
 * @property string|null $model
 * @property string|null $foreign_id
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
class InvoicesAttachment extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
