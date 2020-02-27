<?php
declare(strict_types=1);

namespace LilProjects\Model\Entity;

use Cake\ORM\Entity;

/**
 * Project Entity
 *
 * @property string $id
 * @property string $owner_id
 * @property string $no
 * @property string $title
 * @property float $lat
 * @property float $lon
 * @property string $ico
 * @property string $colorize
 * @property bool $active
 * @property \Cake\I18n\Time $created
 * @property \Cake\I18n\Time $modified
 *
 * @property \LilProjects\Model\Entity\Owner $owner
 */
class Project extends Entity
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
        'owner_id' => true,
        'status_id' => true,
        'no' => true,
        'title' => true,
        'lat' => true,
        'lon' => true,
        'ico' => true,
        'colorize' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'owner' => true,
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
