<?php
declare(strict_types=1);

namespace LilExpenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * Payment Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $account_id
 * @property \Cake\I18n\FrozenDate|null $dat_happened
 * @property string|null $descript
 * @property float|null $amount
 * @property string|null $sepa_id
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property array $expenses
 */
class Payment extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     * Note that '*' is set to true, which allows all unspecified fields to be
     * mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'owner_id' => true,
        'account_id' => true,
        'dat_happened' => true,
        'descript' => true,
        'amount' => true,
        'sepa_id' => true,
        'created' => true,
        'modified' => true,

        'expenses' => true,
    ];
}
