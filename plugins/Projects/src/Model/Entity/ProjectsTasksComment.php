<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsTasksComment Entity
 *
 * @property string $id
 * @property string|null $task_id
 * @property string|null $user_id
 * @property int $kind
 * @property string|null $descript
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Projects\Model\Entity\Task $task
 * @property \Projects\Model\Entity\User $user
 */
class ProjectsTasksComment extends Entity
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
        'task_id' => true,
        'user_id' => true,
        'kind' => true,
        'descript' => true,
        'created' => true,
        'modified' => true,
        'task' => true,
        'user' => true,
    ];
}
