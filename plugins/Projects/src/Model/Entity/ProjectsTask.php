<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsTask Entity
 *
 * @property string $id
 * @property string|null $project_id
 * @property string|null $milestone_id
 * @property string|null $user_id
 * @property string|null $assigned_user_id
 * @property int $no
 * @property int $status
 * @property string|null $title
 * @property string|null $descript
 * @property \Cake\I18n\DateTime|null $reopened
 * @property \Cake\I18n\Date|null $closed
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Projects\Model\Entity\Project $project
 * @property \Projects\Model\Entity\User $user
 * @property \Projects\Model\Entity\Milestone $milestone
 */
class ProjectsTask extends Entity
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
        'project_id' => true,
        'milestone_id' => true,
        'user_id' => true,
        'assigned_user_id' => true,
        'no' => true,
        'status' => true,
        'title' => true,
        'descript' => true,
        'reopened' => true,
        'closed' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'user' => true,
        'milestone' => true,
    ];
}
