<?php
declare(strict_types=1);

namespace LilProjects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsCompMaterial Entity
 *
 * @property string $id
 * @property string|null $composite_id
 * @property bool $is_group
 * @property int $sort_order
 * @property string|null $descript
 * @property string|null $thickness
 *
 * @property \LilProjects\Model\Entity\Composite $composite
 */
class ProjectsCompMaterial extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'composite_id' => true,
        'is_group' => true,
        'sort_order' => true,
        'descript' => true,
        'thickness' => true,
        'composite' => true,
    ];
}
