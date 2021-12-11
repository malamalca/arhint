<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoicesTax Entity.
 */
class InvoicesTax extends Entity
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
