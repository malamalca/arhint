<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;

/**
 * Item Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $vat_id
 * @property string|null $descript
 * @property float $qty
 * @property string|null $unit
 * @property float $price
 * @property float $discount
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class Item extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
        'vat' => true,
    ];
}
