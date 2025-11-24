<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsMilestone Entity
 *
 * @property string $id
 * @property string|null $project_id
 * @property string|null $title
 * @property \Cake\I18n\DateTime|null $due
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Projects\Model\Entity\Project $project
 */
class ProjectsMilestone extends Entity
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
        'title' => true,
        'due' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
    ];
}
