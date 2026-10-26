<?php
declare(strict_types=1);

namespace App\Event;

use App\Lib\DocumentAiIndexer;
use App\Model\Table\LogsTable;
use App\View\Widget\DurationWidget;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Queue\QueueManager;
use Cake\Routing\Router;
use Cake\Utility\Inflector;
use DateTimeInterface;
use Documents\Model\Table\DocumentsTable;
use Documents\Model\Table\InvoicesTable;
use Laminas\Diactoros\UploadedFile;

class AppEvents implements EventListenerInterface
{
    /**
     * @var array<int,mixed> $attachments
     */
    private ?array $attachments = null;

    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'Model.beforeMarshal' => 'marshalDurationAndAttachments',
            'Model.afterSave' => 'updateModelAttachments',
            'App.Form.Documents.Invoices.edit' => 'addAttachmentFormLines',
            'App.Form.Documents.Documents.edit' => 'addAttachmentFormLines',
        ];
    }

    /**
     * Before post data is converted to entity.
     *
     * @param \Cake\Event\Event $event The event object.
     * @param \ArrayObject $data Post data.
     * @param \ArrayObject $options Additional options from controller.
     * @return void
     */
    public function marshalDurationAndAttachments(Event $event, ArrayObject $data, ArrayObject $options): void
    {
        DurationWidget::marshalDurationFields($data);

        if (in_array(get_class($event->getSubject()), [DocumentsTable::class, InvoicesTable::class])) {
            // set modifed so the dirty attribute is set and beforeSave is triggered
            $data['modified'] = (new DateTime())->setTimezone('UTC')->toDateTimeString();
            if (isset($data['documents_attachments'])) {
                foreach ((array)$data['documents_attachments'] as $key => $attch) {
                    if (
                        !isset($attch['filename']) ||
                        !($attch['filename'] instanceof UploadedFile) ||
                        $attch['filename']->getError() !== UPLOAD_ERR_OK
                    ) {
                        unset($data['documents_attachments'][$key]);
                    }
                }
                $this->attachments = $data['documents_attachments'];
            }
        }
    }

    /**
     * Update attachments
     *
     * @param \Cake\Event\Event $event Event object
     * @param \Cake\Datasource\EntityInterface $entity Entity object
     * @param \ArrayObject $options Options array
     * @return void
     */
    public function updateModelAttachments(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (in_array(get_class($event->getSubject()), [DocumentsTable::class, InvoicesTable::class])) {
            $savedAttachmentIds = [];
            if (!empty($this->attachments)) {
                $AttachmentsTable = TableRegistry::getTableLocator()->get('Attachments');

                foreach ($this->attachments as $attch) {
                    if (
                        isset($attch['filename']) &&
                        ($attch['filename'] instanceof UploadedFile) &&
                        $attch['filename']->getError() === UPLOAD_ERR_OK
                    ) {
                        $attachment = $AttachmentsTable->newEntity(
                            array_merge($attch, ['foreign_id' => $entity->get('id')]),
                        );
                        $savedAttachment = $AttachmentsTable->save(
                            $attachment,
                            ['uploadedFilename' => [
                                (string)$attch['filename']->getClientFilename() => $attch['filename'],
                            ]],
                        );
                        if ($savedAttachment) {
                            $savedAttachmentIds[] = (string)$savedAttachment->get('id');
                        }
                    }
                }

                $this->attachments = null;
            }

            // Index new documents (and the text of their attachments) in the vector database.
            if ($event->getSubject() instanceof DocumentsTable && $entity->isNew()) {
                // Imported documents have no owner; the logged-in user stands in for it.
                $identity = Router::getRequest()?->getAttribute('identity');
                (new DocumentAiIndexer())->queue(
                    $entity,
                    $savedAttachmentIds,
                    fallbackUserId: $identity ? (string)$identity->getIdentifier() : null,
                );
            }
        }

        // Queue a Job which will process the log entry with AI.
        // Supports both generic Logs table and Projects plugin logs.
        $logTables = [LogsTable::class];
        if (in_array(get_class($event->getSubject()), $logTables)) {
            // Trigger on new entries, or when core event data actually changes.
            // Skips purely timestamp/audit-only saves to avoid burning AI tokens unnecessarily.
            if ($entity->isNew() || $entity->isDirty('descript') || $entity->isDirty('action')) {
                // Convert entity to scalar-friendly array for queue serialization.
                $scalarEntity = $this->entityToScalars($entity);

                QueueManager::push('AiProcessLog', [
                    'user_id' => (string)$entity->get('user_id'),
                    'entity' => $scalarEntity,
                    'job_id' => (string)$entity->get('id'),
                ]);
            }
        }
    }

    /**
     * Recursively flatten an entity to scalars only.
     *
     * Queue serialization requires scalar values, so DateTime objects and nested
     * entities must be converted to plain strings/numbers.
     *
     * @param \Cake\Datasource\EntityInterface $entity The entity to flatten.
     * @return array<string, mixed>
     */
    private function entityToScalars(EntityInterface $entity): array
    {
        $data = $entity->toArray();
        array_walk_recursive($data, static function (&$value): void {
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif (is_object($value) && method_exists($value, '__toString')) {
                $value = (string)$value;
            }
        });

        return $data;
    }

    /**
     * Add attachment form lines to documents and invoices edit forms.
     *
     * @param \Cake\Event\Event $event Event.
     * @param mixed $formLines Form lines.
     * @return void
     */
    public function addAttachmentFormLines(Event $event, mixed $formLines): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();

        $eventName = $event->getName();
        $eventParts = explode('.', $eventName);
        $modelName = $eventParts[3] ?? '';
        if (!in_array($modelName, ['Documents', 'Invoices'], true)) {
            return;
        }

        $modelName = Inflector::singularize($modelName);

        $attachmentLines = [
            'fs_attachments_start' => '<fieldset>',
            'fs_attachments_legend' => sprintf('<legend>%s</legend>', __('Archive')),
        ];

        // When an invoice was imported from a PDF, that PDF is already pending (stashed in the
        // session by InvoiceImportForm and attached after the first save by
        // InvoicesController::attachImportedPdf()). Show it by name with a remove option instead
        // of an empty file input, so the import reuses this single attachment section.
        $importPdfName = $view->get('importPdfName');
        if (is_string($importPdfName) && $importPdfName !== '') {
            $attachmentLines['attachment_import_name'] = sprintf(
                '<div class="helper-text import-pdf-pending">%s</div>',
                h(__('The uploaded PDF will be attached: {0}', $importPdfName)),
            );
            $attachmentLines['attachment_import_remove'] = [
                'method' => 'control',
                'parameters' => [
                    'field' => 'remove_import_pdf',
                    'options' => [
                        'type' => 'checkbox',
                        'label' => __('Remove (do not attach this PDF)'),
                    ],
                ],
            ];
        } else {
            $attachmentLines['file.name.0'] = [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.filename',
                    'options' => [
                        'type' => 'file',
                        'label' => false,
                    ],
                ],
            ];
            $attachmentLines['file.document_id.0'] = [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.document_id',
                    'options' => [
                        'type' => 'hidden',
                    ],
                ],
            ];
            $attachmentLines['file.model.0'] = [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.model',
                    'options' => [
                        'type' => 'hidden',
                        'value' => $modelName,
                    ],
                ],
            ];
        }

        $attachmentLines['fs_attachments_end'] = '</fieldset>';

        $view->Lil->insertIntoArray($formLines->form['lines'], $attachmentLines, ['before' => 'submit']);
    }
}
