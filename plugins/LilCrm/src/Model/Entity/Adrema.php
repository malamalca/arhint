<?php
declare(strict_types=1);

namespace LilCrm\Model\Entity;

use Cake\ORM\Entity;

/**
 * Adrema Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $title
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class Adrema extends Entity
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
