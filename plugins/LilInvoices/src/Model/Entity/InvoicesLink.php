<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoicesLink Entity.
 *
 * @property string $id
 * @property string|null $link_id
 * @property string|null $invoice_id
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class InvoicesLink extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<bool>
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
