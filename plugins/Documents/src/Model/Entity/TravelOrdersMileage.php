<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

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
class TravelOrdersMileage extends Entity
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
}
