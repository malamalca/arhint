<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoicesTemplate Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $kind
 * @property string|null $title
 * @property string|null $body
 * @property bool $main
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class InvoicesTemplate extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
