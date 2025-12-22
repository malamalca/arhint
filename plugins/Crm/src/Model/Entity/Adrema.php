<?php
declare(strict_types=1);

namespace Crm\Model\Entity;

use Cake\ORM\Entity;
use JsonException;

/**
 * Adrema Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $project_id
 * @property string|null $kind
 * @property string|null $kind_type
 * @property string|null $title
 * @property array|null $user_data
 * @property string|null $user_values
 * @property array|null $data
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

    /**
     * Decode user values JSON
     *
     * @return array<string, mixed>|null Decoded values or empty string on failure
     */
    protected function _getUserData(): ?array
    {
        if (empty($this->user_values)) {
            return null;
        }
        try {
            return json_decode($this->user_values, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return null;
        }
    }
}
