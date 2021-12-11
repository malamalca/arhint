<?php
declare(strict_types=1);

namespace LilTasks\Model\Entity;

use Cake\ORM\Entity;

/**
 * Task Entity.
 *
 * @property string $id
 * @property string $owner_id
 * @property string $user_id
 * @property string $tasker_id
 * @property \LilTasks\Model\Entity\Owner $owner
 * @property string $model
 * @property string $foreign_id
 * @property \LilTasks\Model\Entity\Foreign $foreign
 * @property string $title
 * @property string $descript
 * @property \Cake\I18n\Time|null $started
 * @property \Cake\I18n\Time|null $deadline
 * @property \Cake\I18n\Time|null $completed
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 */
class Task extends Entity
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
        '*' => true,
        'id' => false,
    ];
}
