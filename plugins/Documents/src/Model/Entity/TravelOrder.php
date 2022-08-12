<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * TravelOrder Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $payer_id
 * @property string|null $employee_id
 * @property string|null $vehicle_id
 * @property string $counter_id
 * @property string|null $doc_type
 * @property string|null $tpl_header_id
 * @property string|null $tpl_body_id
 * @property string|null $tpl_footer_id
 * @property int $attachment_count
 * @property int|null $counter
 * @property string|null $no
 * @property \Cake\I18n\FrozenDate|null $dat_issue
 * @property string|null $location
 * @property string|null $descript
 * @property string|null $title
 * @property string|null $taskee
 * @property \Cake\I18n\FrozenDate|null $dat_task
 * @property \Cake\I18n\FrozenTime|null $departure
 * @property \Cake\I18n\FrozenTime|null $arrival
 * @property string|null $vehicle_registration
 * @property string|null $vehicle_owner
 * @property string|null $advance
 * @property \Cake\I18n\FrozenDate|null $dat_advance
 * @property float|null $net_total
 * @property float|null $total
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \Documents\Model\Entity\DocumentsCounter $documents_counter
 * @property \Documents\Model\Entity\DocumentsClient $payer
 */
class TravelOrder extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'owner_id' => true,
        'payer_id' => true,
        'employee_id' => true,
        'vehicle_id' => true,
        'counter_id' => true,
        'doc_type' => true,
        'tpl_header_id' => true,
        'tpl_body_id' => true,
        'tpl_footer_id' => true,
        'attachment_count' => true,
        'counter' => true,
        'no' => true,
        'dat_issue' => true,
        'location' => true,
        'descript' => true,
        'title' => true,
        'taskee' => true,
        'dat_task' => true,
        'departure' => true,
        'arrival' => true,
        'vehicle_registration' => true,
        'vehicle_owner' => true,
        'advance' => true,
        'dat_advance' => true,
        'net_total' => true,
        'total' => true,
        'created' => true,
        'modified' => true,
        'owner' => true,
        'payer' => true,
        'employee' => true,
        'vehicle' => true,
        'tpl_header' => true,
        'tpl_body' => true,
        'tpl_footer' => true,
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
