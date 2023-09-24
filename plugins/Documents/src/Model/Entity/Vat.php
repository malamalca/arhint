<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * Vat Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $descript
 * @property float $percent
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Vat extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->descript;
    }
}
