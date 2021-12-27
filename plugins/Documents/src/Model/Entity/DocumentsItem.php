<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * DocumentsItem Entity.
 *
 * @property string $id
 * @property string|null $document_id
 * @property string|null $item_id
 * @property string|null $vat_id
 * @property string|null $vat_title
 * @property float|null $vat_percent
 * @property string|null $descript
 * @property float $qty
 * @property string|null $unit
 * @property float $price
 * @property float $discount
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property float $items_total
 * @property float $discount_total
 * @property float $net_total
 * @property float $tax_total
 */
class DocumentsItem extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<bool>
     */
    protected $_accessible = [
        'id' => false,
        'document_id' => true,
        'item_id' => true,
        'vat_id' => true,
        'vat_title' => true,
        'vat_percent' => true,
        'descript' => true,
        'qty' => true,
        'unit' => true,
        'price' => true,
        'discount' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Calculated property "items_total". Returns item_price * count
     *
     * @return float
     */
    protected function _getItemsTotal()
    {
        return round($this->price * $this->qty, 4);
    }

    /**
     * Calculated property "discount_total". Returns item_price * count * discount%
     *
     * @return float
     */
    protected function _getDiscountTotal()
    {
        return round($this->items_total * $this->discount / 100, 4);
    }

    /**
     * Calculated property "tax_total". Returns ((item_price * count) - discount) * vat%
     *
     * @return float
     */
    protected function _getTaxTotal()
    {
        return round($this->net_total * $this->vat_percent / 100, 2);
    }

    /**
     * Calculated property "net_total". Returns (item_price * count) - discount
     *
     * @return float
     */
    protected function _getNetTotal()
    {
        return round($this->items_total - $this->discount_total, 2);
    }

    /**
     * Calculated property "total". Returns (item_price * count) - discount + tax
     *
     * @return float
     */
    protected function _getTotal()
    {
        return round($this->net_total + $this->tax_total, 2);
    }
}
