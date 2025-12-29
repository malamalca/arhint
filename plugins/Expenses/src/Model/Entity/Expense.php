<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * Expense Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $model
 * @property string|null $foreign_id
 * @property \Cake\I18n\Date|null $dat_happened
 * @property string|null $month
 * @property string|null $title
 * @property float|null $net_total
 * @property float|null $total
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Expense $expense
 * @property \Documents\Model\Entity\Invoice $invoice
 */
class Expense extends Entity
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
        'payments' => true,
    ];
}
