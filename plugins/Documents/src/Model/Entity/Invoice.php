<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use App\Lib\AISerializableInterface;
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
 * @property \Cake\I18n\Date|null $dat_sign
 * @property \Cake\I18n\Date|null $dat_issue
 * @property \Cake\I18n\Date|null $dat_service
 * @property \Cake\I18n\Date|null $dat_expire
 * @property \Cake\I18n\Date|null $dat_approval
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
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Documents\Model\Entity\DocumentsCounter $documents_counter
 * @property \Documents\Model\Entity\DocumentsClient $issuer
 * @property \Documents\Model\Entity\DocumentsClient $buyer
 * @property \Documents\Model\Entity\DocumentsClient $receiver
 * @property array $invoices_items
 * @property array $invoices_taxes
 * @property array $attachments
 *
 * @property array $deleteTaxesList
 * @property array $deleteItemsList
 */
class Invoice extends Entity implements AISerializableInterface
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Populates "counter" and "no" fields from counter data. Increases
     *
     * @return void
     */
    public function getNextCounterNo(): void
    {
        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

        // update documents counter
        $counter = $DocumentsCounters->get($this->counter_id);
        $this->counter = $counter->counter + 1;

        // generate documents' `no` according to counter's `mask`
        if (!empty($counter->mask)) {
            $this->no = (string)$DocumentsCounters->generateNo($counter->toArray());
        }

        // update counter
        $DocumentsCounters->updateAll(['counter' => $counter->counter + 1], ['id' => $counter->id]);
    }

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'project_id' => $this->project_id,
            'doc_type' => $this->doc_type,
            'no' => $this->no,
            'title' => $this->title,
            'dat_issue' => $this->dat_issue ? (string)$this->dat_issue : null,
            'dat_expire' => $this->dat_expire ? (string)$this->dat_expire : null,
            'net_total' => $this->net_total,
            'total' => $this->total,
            'view_url' => $this->view_url ?? null,
            'items' => array_map(fn($e) => $e->toAIArray(), $this->invoices_items ?? []),
            'taxes' => array_map(fn($e) => $e->toAIArray(), $this->invoices_taxes ?? []),
        ];
    }
}
