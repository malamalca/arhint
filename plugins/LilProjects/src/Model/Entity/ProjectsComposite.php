<?php
declare(strict_types=1);

namespace LilProjects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsComposite Entity
 *
 * @property string $id
 * @property string|null $project_id
 * @property string|null $no
 * @property string|null $title
 *
 * @property \LilProjects\Model\Entity\Project $project
 */
class ProjectsComposite extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'project_id' => true,
        'no' => true,
        'title' => true,
        'project' => true,
    ];

    /**
     * Returns friendly project name
     *
     * @return string
     */
    public function getName()
    {
        return $this->no . ' - ' . $this->title;
    }
}
