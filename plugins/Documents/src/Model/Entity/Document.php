<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

/**
 * Document Entity
 *
 * @property string $id
 * @property string|null $owner_id
 * @property string|null $user_id
 * @property string|null $contact_id
 * @property string $counter_id
 * @property string $project_id
 * @property string|null $tpl_header_id
 * @property string|null $tpl_body_id
 * @property string|null $tpl_footer_id
 * @property int $attachments_count
 * @property int $counter
 * @property string|null $no
 * @property \Cake\I18n\FrozenDate|null $dat_issue
 * @property string|null $title
 * @property string|null $descript
 * @property string|null $location
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \Documents\Model\Entity\DocumentsClient $issuer
 * @property \Documents\Model\Entity\DocumentsClient $receiver
 * @property \Documents\Model\Entity\DocumentsCounter $documents_counter
 * @property \Documents\Model\Entity\Contact $contact
 * @property \Documents\Model\Entity\Project $project
 * @property \Documents\Model\Entity\TplHeader $tpl_header
 * @property \Documents\Model\Entity\TplBody $tpl_body
 * @property \Documents\Model\Entity\TplFooter $tpl_footer
 * @property \Documents\Model\Entity\DocumentsAttachment[] $documents_attachments
 * @property \Documents\Model\Entity\DocumentsClient[] $documents_clients
 * @property \Documents\Model\Entity\DocumentsLink[] $documents_links
 */
class Document extends Entity
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
        'user_id' => true,
        'contact_id' => true,
        'counter_id' => true,
        'project_id' => true,
        'tpl_header_id' => true,
        'tpl_body_id' => true,
        'tpl_footer_id' => true,
        'attachments_count' => true,
        'counter' => true,
        'no' => true,
        'dat_issue' => true,
        'title' => true,
        'descript' => true,
        'location' => true,
        'created' => true,
        'modified' => true,
        'owner' => true,
        'user' => true,
        'contact' => true,
        'project' => true,
        'tpl_header' => true,
        'tpl_body' => true,
        'tpl_footer' => true,
        'documents_attachments' => true,
        'documents_clients' => true,
        'documents_links' => true,
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
