<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Invoice Entity.
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $user_id
 * @property string|null $contact_id
 * @property string $counter_id
 * @property string|null $project_id
 * @property string|null $doc_type
 * @property string|null $tpl_header_id
 * @property string|null $tpl_body_id
 * @property string|null $tpl_footer_id
 * @property int $attachments_count
 * @property int|null $counter
 * @property string|null $no
 * @property string|null $title
 * @property string|null $descript
 * @property string|null $signed
 * @property \Cake\I18n\FrozenDate|null $dat_sign
 * @property \Cake\I18n\FrozenDate|null $dat_issue
 * @property \Cake\I18n\FrozenDate|null $dat_service
 * @property \Cake\I18n\FrozenDate|null $dat_expire
 * @property \Cake\I18n\FrozenDate|null $dat_approval
 * @property float|null $net_total
 * @property float|null $total
 * @property bool $inversed_tax
 * @property int|null $pmt_kind
 * @property string|null $pmt_sepa_type
 * @property string|null $pmt_type
 * @property string|null $pmt_module
 * @property string|null $pmt_ref
 * @property string|null $pmt_descript
 * @property string|null $location
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \Documents\Model\Entity\DocumentsCounter $documents_counter
 * @property \Documents\Model\Entity\DocumentsClient $issuer
 * @property \Documents\Model\Entity\DocumentsClient $buyer
 * @property \Documents\Model\Entity\DocumentsClient $receiver
 * @property array $invoices_items
 * @property array $invoices_taxes
 * @property array $documents_attachments
 *
 * @property array $deleteTaxesList
 * @property array $deleteItemsList
 */
class Invoice extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Populates "counter" and "no" fields from counter data. Increases
     *
     * @return void
     */
    public function getNextCounterNo()
    {
        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

        // update documents counter
        $counter = $DocumentsCounters->get($this->counter_id);
        $this->counter = $counter->counter + 1;

        // generate documents' `no` according to counter's `mask`
        if (!empty($counter->mask)) {
            $this->no = $DocumentsCounters->generateNo($counter->toArray());
        }

        // update counter
        $DocumentsCounters->updateAll(['counter' => $counter->counter + 1], ['id' => $counter->id]);
    }
}
