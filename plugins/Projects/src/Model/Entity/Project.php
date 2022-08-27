<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

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
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \Projects\Model\Entity\Owner $owner
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
     * @var array<string, bool>
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

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->no . ' - ' . $this->title;
    }
}
