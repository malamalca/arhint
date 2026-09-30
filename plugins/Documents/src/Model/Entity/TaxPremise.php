<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaxPremise Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $no
 * @property string|null $title
 * @property string $kind
 * @property string|null $casadral_number
 * @property string|null $building_number
 * @property string|null $building_section_number
 * @property string|null $street
 * @property string|null $house_number
 * @property string|null $house_number_additional
 * @property string|null $community
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $mo_type
 * @property \Cake\I18n\Date $validity_date
 * @property bool $closed
 * @property string|null $sw_taxno
 * @property string|null $sw_title
 * @property bool $active
 * @property string|null $last_request
 * @property string|null $last_response
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class TaxPremise extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
        'owner_id' => false,
        'active' => false,
        'last_request' => false,
        'last_response' => false,
    ];

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->no . ' - ' . (string)$this->title;
    }
}
