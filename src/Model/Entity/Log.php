<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Log Entity
 *
 * @property string $id
 * @property string|null $model
 * @property string|null $foreign_id
 * @property string|null $user_id
 * @property string|null $descript
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 * @property \App\Model\Entity\User $user
 */
class Log extends Entity
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
        'model' => true,
        'foreign_id' => true,
        'user_id' => true,
        'descript' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
    ];
}
