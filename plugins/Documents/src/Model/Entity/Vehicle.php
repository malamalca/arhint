<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * Vehicle Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $title
 * @property string|null $registration
 * @property string|null $owner
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Vehicle extends Entity
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
        'owner_id' => true,
        'title' => true,
        'registration' => true,
        'owner' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->title . '(' . $this->registration . ')';
    }
}
