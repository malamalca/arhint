<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsWorkhour Entity
 *
 * @property string $id
 * @property string $project_id
 * @property string $user_id
 * @property \Cake\I18n\DateTime $started
 * @property int $duration
 * @property \Cake\I18n\Date $dat_confirmed
 * @property string $descript
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Projects\Model\Entity\Project $project
 * @property \Projects\Model\Entity\User $user
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'project_id' => true,
        'user_id' => true,
        'started' => true,
        'duration' => true,
        'dat_confirmed' => true,
        'descript' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'user' => true,
    ];
}
