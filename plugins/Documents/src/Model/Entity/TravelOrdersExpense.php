<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\ORM\Entity;

/**
 * TravelOrdersExpense Entity
 *
 * @property string $id
 * @property string $travel_order_id
 * @property \Cake\I18n\DateTime|null $start_time
 * @property \Cake\I18n\DateTime|null $end_time
 * @property string|null $type
 * @property string|null $description
 * @property float|null $quantity
 * @property float|null $price
 * @property string|null $currency
 * @property float|null $total
 * @property float|null $approved_total
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Documents\Model\Entity\TravelOrder $travel_order
 */
class TravelOrdersExpense extends Entity implements AISerializableInterface
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'travel_order_id' => true,
        'start_time' => true,
        'end_time' => true,
        'type' => true,
        'description' => true,
        'quantity' => true,
        'price' => true,
        'currency' => true,
        'total' => true,
        'approved_total' => true,
        'created' => true,
        'modified' => true,
        'travel_order' => true,
    ];

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'type' => $this->type,
            'description' => $this->description,
            'start_time' => $this->start_time ? (string)$this->start_time : null,
            'end_time' => $this->end_time ? (string)$this->end_time : null,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'currency' => $this->currency,
            'total' => $this->total,
            'approved_total' => $this->approved_total,
        ];
    }
}
