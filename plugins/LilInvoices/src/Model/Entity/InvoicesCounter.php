<?php
declare(strict_types=1);

namespace LilInvoices\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoicesCounter Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $kind
 * @property string|null $doc_type
 * @property int|null $expense
 * @property int $counter
 * @property string|null $title
 * @property string|null $mask
 * @property string|null $layout
 * @property string|null $layout_title
 * @property string|null $template_descript
 * @property string|null $tpl_header_id
 * @property string|null $tpl_body_id
 * @property string|null $tpl_footer_id
 * @property bool $active
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class InvoicesCounter extends Entity
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

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->title;
    }
}
