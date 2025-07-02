<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\Core\Configure;
use Cake\ORM\Entity;

/**
 * DocumentsAttachment Entity.
 *
 * @property string $id
 * @property string|null $foreign_id
 * @property string|null $model
 * @property string|null $filename
 * @property string|null $ext
 * @property string|null $mimetype
 * @property int|null $filesize
 * @property string|null $description
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Attachment extends Entity
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
     * Returns full path for attachment
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return Configure::read('App.uploadFolder') . DS . $this->filename;
    }
}
