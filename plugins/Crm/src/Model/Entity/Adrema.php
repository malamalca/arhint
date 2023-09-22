<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\ORM\Entity;

/**
 * Adrema Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $title
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class Adrema extends Entity
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
}
