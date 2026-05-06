<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\ORM\Entity;

/**
 * TravelOrdersMileage Entity
 *
 * @property string $id
 * @property string $travel_order_id
 * @property \Cake\I18n\DateTime|null $start_time
 * @property \Cake\I18n\DateTime|null $end_time
 * @property string|null $road_description
 * @property float|null $distance_km
 * @property float|null $price_per_km
 * @property float|null $total
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Documents\Model\Entity\TravelOrder $travel_order
 */
class TravelOrdersMileage extends Entity implements AISerializableInterface
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
        'road_description' => true,
        'distance_km' => true,
        'price_per_km' => true,
        'total' => true,
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
            'road_description' => $this->road_description,
            'start_time' => $this->start_time ? (string)$this->start_time : null,
            'end_time' => $this->end_time ? (string)$this->end_time : null,
            'distance_km' => $this->distance_km,
            'price_per_km' => $this->price_per_km,
            'total' => $this->total,
        ];
    }
}
