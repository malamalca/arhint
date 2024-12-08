<?php
declare(strict_types=1);

namespace App\Model\Entity;

use App\AppPluginsEnum;
use Cake\ORM\Entity;

/**
 * DashboardNote Entity
 *
 * @property string $id
 * @property string|null $user_id
 * @property string $note
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class DashboardNote extends Entity {
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
        'user_id' => true,
        'note' => true,
        'created' => true,
        'modified' => true,
    ];
}
