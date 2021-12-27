<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsUser Entity
 *
 * @property string $id
 * @property string|null $project_id
 * @property string|null $user_id
 *
 * @property \Projects\Model\Entity\Project $project
 * @property \Projects\Model\Entity\User $user
 */
class ProjectsUser extends Entity
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
        'project_id' => true,
        'user_id' => true,
        'project' => true,
        'user' => true,
    ];
}
