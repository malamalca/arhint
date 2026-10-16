<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Documents\Lib\RfReference;
use Documents\Lib\SiReference;

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

        $this->generatePaymentReference($counter);

        // update counter
        $DocumentsCounters->updateAll(['counter' => $counter->counter + 1], ['id' => $counter->id]);
    }

    /**
     * Payment reference of the document, built from its three payment fields.
     *
     * The electronic form is written without spaces and goes into the UPN QR, eSlog and
     * SEPA. The form the reference is written in separates the type and the model from the
     * content (SI12 0000002026023); an RF reference is written in groups of four
     * characters (RF71 2348 231).
     *
     * @param bool $visual Reference in the form it is written in.
     * @return string
     */
    public function paymentReference(bool $visual = false): string
    {
        $type = (string)$this->pmt_type . (string)$this->pmt_module;
        $content = (string)$this->pmt_ref;

        if (!$visual || $content === '') {
            return $type . $content;
        }

        if (RfReference::isSelected($this->pmt_type)) {
            return RfReference::visual($type . $content);
        }

        return trim($type . ' ' . $content);
    }

    /**
     * Fills payment type, model and reference with a generated payment reference.
     *
     * A Slovenian reference (SI) is generated, or a creditor reference by ISO 11649 when
     * the counter's `pmt_mod` is RF. Only issued documents with an empty reference are
     * filled, so an imported or a hand entered reference is never overwritten. The
     * reference is derived from the document number, which makes it the same every time it
     * is generated for the same document.
     *
     * @param \Documents\Model\Entity\DocumentsCounter $counter Counter of this document.
     * @return void
     */
    public function generatePaymentReference(DocumentsCounter $counter): void
    {
        if ($counter->direction !== 'issued' || !empty($this->pmt_ref)) {
            return;
        }

        if (RfReference::isSelected($counter->pmt_mod)) {
            $this->generateRfReference($counter);

            return;
        }

        $this->generateSiReference($counter);
    }

    /**
     * Fills the payment fields with a Slovenian reference (SI).
     *
     * @param \Documents\Model\Entity\DocumentsCounter $counter Counter of this document.
     * @return void
     */
    protected function generateSiReference(DocumentsCounter $counter): void
    {
        $model = SiReference::normalizeModel($counter->pmt_mod) ?? SiReference::MODEL_DEFAULT;
        $content = SiReference::generate($model, $this->paymentReferenceContent($counter, true));

        if ($content === null) {
            return;
        }

        $this->pmt_type = SiReference::PREFIX;
        $this->pmt_module = $model;
        $this->pmt_ref = $content;
    }

    /**
     * Fills the payment fields with a creditor reference by ISO 11649 (RF).
     *
     * @param \Documents\Model\Entity\DocumentsCounter $counter Counter of this document.
     * @return void
     */
    protected function generateRfReference(DocumentsCounter $counter): void
    {
        $content = substr(
            $this->paymentReferenceContent($counter, false),
            -RfReference::MAX_CONTENT_LENGTH,
        );
        $checkDigits = RfReference::checkDigits($content);

        if ($checkDigits === null) {
            return;
        }

        $this->pmt_type = RfReference::PREFIX;
        $this->pmt_module = $checkDigits;
        $this->pmt_ref = RfReference::normalizeContent($content);
    }

    /**
     * Content the generated reference is built from.
     *
     * Counter's `pmt_ref` template is used when set, otherwise the document number,
     * prefixed with the year of issue when it does not start with it already. Characters
     * that the reference does not allow are dropped: an SI reference keeps the digits, an
     * RF reference the digits and the letters.
     *
     * @param \Documents\Model\Entity\DocumentsCounter $counter Counter of this document.
     * @param bool $digitsOnly Keep the digits only.
     * @return string
     */
    protected function paymentReferenceContent(DocumentsCounter $counter, bool $digitsOnly): string
    {
        $no = (string)$this->no;
        $date = $this->dat_issue ?? Date::now();
        $year = (string)$date->i18nFormat('yyyy');
        $drop = $digitsOnly ? '/\D/' : '/[^A-Za-z0-9]/';

        if (!empty($counter->pmt_ref)) {
            return strtoupper((string)preg_replace($drop, '', strtr($counter->pmt_ref, [
                '[[year]]' => $year,
                '[[month]]' => (string)$date->i18nFormat('MM'),
                '[[no]]' => $no,
            ])));
        }

        $content = strtoupper(
            (string)preg_replace($drop, '', $no !== '' ? $no : (string)$this->counter),
        );

        return str_starts_with($content, $year) ? $content : $year . $content;
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
