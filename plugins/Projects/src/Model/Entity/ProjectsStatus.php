<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsStatus Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $title
 *
 * @property \Projects\Model\Entity\Owner $owner
 */
class ProjectsStatus extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'owner_id' => true,
        'title' => true,
        'owner' => true,
    ];
}
