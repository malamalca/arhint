<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * DocumentsCounter Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $kind
 * @property string|null $direction
 * @property string|null $doc_type
 * @property int|null $expense
 * @property int $counter
 * @property string|null $title
 * @property string|null $mask
 * @property string|null $pmt_mod
 * @property string|null $pmt_ref
 * @property int|null $pmt_days
 * @property string|null $template_descript
 * @property string|null $tpl_header_id
 * @property string|null $tpl_body_id
 * @property string|null $tpl_footer_id
 * @property bool $primary
 * @property bool $active
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 */
class DocumentsCounter extends Entity
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

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->title;
    }

    /**
     * Checks if current document is Document
     *
     * @return bool
     */
    public function isInvoice()
    {
        //return in_array($this->doc_type, (array)Configure::read('Documents.invoiceDocTypes'));
        return $this->kind == 'Invoices';
    }
}
