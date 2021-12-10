<?php
declare(strict_types=1);

namespace LilProjects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsMaterial Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $group_id
 * @property string|null $descript
 * @property string $thickness
 *
 * @property \LilProjects\Model\Entity\Owner $owner
 * @property \LilProjects\Model\Entity\Group $group
 */
class ProjectsMaterial extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'owner_id' => true,
        'group_id' => true,
        'descript' => true,
        'thickness' => true,
        'owner' => true,
        'group' => true,
    ];
}
