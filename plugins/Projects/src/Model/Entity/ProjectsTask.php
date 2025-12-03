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
 * @property int $no
 * @property string|null $title
 * @property string|null $descript
 * @property \Cake\I18n\Date|null $date_complete
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
        'user_id' => true,
        'milestone_id' => true,
        'no' => true,
        'title' => true,
        'descript' => true,
        'date_complete' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'user' => true,
        'milestone' => true,
    ];
}
