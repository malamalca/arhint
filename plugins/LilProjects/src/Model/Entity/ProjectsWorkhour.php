<?php
declare(strict_types=1);

namespace LilProjects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsWorkhour Entity
 *
 * @property string $id
 * @property string $project_id
 * @property string $user_id
 * @property \Cake\I18n\Time $started
 * @property int $duration
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \LilProjects\Model\Entity\Project $project
 * @property \LilProjects\Model\Entity\User $user
 */
class ProjectsWorkhour extends Entity
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
        'started' => true,
        'duration' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'user' => true,
    ];
}
