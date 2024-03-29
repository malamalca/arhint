<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * PaymentsAccount Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $title
 * @property bool|null $primary
 * @property bool|null $active
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class PaymentsAccount extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     * Note that '*' is set to true, which allows all unspecified fields to be
     * mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
