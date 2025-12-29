<?php
declare(strict_types=1);

namespace App\Event;

use App\View\Helper\ArhintHelper;
use App\View\Widget\DurationWidget;
use ArrayObject;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\View\View;
use Documents\Model\Table\DocumentsTable;
use Documents\Model\Table\InvoicesTable;
use Exception;
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
            'App.dashboard' => 'dashboardPanels',
            'Model.beforeMarshal' => 'marshalDurationAndAttachments',
            'Model.afterSave' => 'updateModelAttachments',
            'App.Form.Documents.Invoices.edit' => 'addAttachmentFormLines',
            'App.Form.Documents.Documents.edit' => 'addAttachmentFormLines',
        ];
    }

    /**
     * Dashboard panels
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $panels Panels data.
     * @return void
     */
    public function dashboardPanels(Event $event, ArrayObject $panels): void
    {
        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();

        /** @var \App\Model\Entity\User $user */
        $user = $controller->getCurrentUser();

        if (extension_loaded('imap') === false) {
            return;
        }

        $imap = $user->getProperty('imap');
        if ($imap && $imap->url && $imap->username && $imap->password) {
            $panels['panels']['email'] = [
                'params' => ['class' => 'dashboard-panel'],
                'lines' => [
                    '<h5>' . __('INBOX Emails') . '</h5>',
                ],
            ];

            //Cache::delete($user->id . '-emails');
            $emails = Cache::remember($user->id . '-emails', function () use ($imap) {
                try {
                    $mbox = imap_open($imap->url, $imap->username, $imap->password);
                    if ($mbox) {
                        $MC = imap_check($mbox);
                        if (!$MC) {
                            throw new Exception('Failure Checking Imap');
                        }
                        $result = imap_fetch_overview($mbox, "1:{$MC->Nmsgs}", 0);

                        if (is_array($result) && count($result) > 0) {
                            usort($result, function ($a, $b) {
                                return $b->udate - $a->udate;
                            });

                            // link email with contact
                            $ContactsEmailsTable = TableRegistry::getTableLocator()->get('Crm.ContactsEmails');
                            foreach ($result as $overview) {
                                $canDecodeEmail = preg_match_all(
                                    '/(?|(?|"([^"]+)"|([^<@]+)) ?<(.+?)>|()(.+?))(?:$|, ?)/',
                                    $overview->from,
                                    $emailsDecoded,
                                    PREG_SET_ORDER,
                                );

                                if ($canDecodeEmail && !empty($emailsDecoded[0][2])) {
                                    $contactsEmails = $ContactsEmailsTable->find()
                                        ->select('contact_id')
                                        ->where(['email' => $emailsDecoded[0][2]])
                                        ->all();

                                    foreach ($contactsEmails as $contactsEmail) {
                                        if (!isset($overview->contacts)) {
                                            $overview->contacts = [];
                                        }
                                        $overview->contacts[] = $contactsEmail->contact_id;
                                    }
                                }
                            }
                        }

                        imap_close($mbox);

                        return $result;
                    }
                } catch (Exception $e) {
                }
            }, 'imap-emails');

            $ArhintHelper = new ArhintHelper(new View());

            foreach ($emails as $overview) {
                preg_match_all(
                    '/(?|(?|"([^"]+)"|([^<@]+)) ?<(.+?)>|()(.+?))(?:$|, ?)/',
                    $overview->from,
                    $emailsDecoded,
                    PREG_SET_ORDER,
                );

                $email = $emailsDecoded[0][2] ?? 'unknown';

                $fromEmail = empty($emailsDecoded[0][1]) ?
                    $email : (h(iconv_mime_decode($emailsDecoded[0][1])) . ' <' . $email . '>');

                $emailLink = '<a href="mailto:' . $email . '" target="_blank">' . h($fromEmail) . '</a>';
                if (isset($overview->contacts)) {
                    $emailLink = sprintf(
                        '<a href="%1$s" target="_blank" style="font-weight: bold;">%2$s</a>',
                        Router::url([
                            'plugin' => 'Crm',
                            'controller' => 'Contacts',
                            'action' => 'view',
                            $overview->contacts[0],
                        ]),
                        h($fromEmail),
                    );
                }

                try {
                    $emailDate = DateTime::parse($overview->date);
                } catch (Exception $e) {
                    $emailDate = new DateTime();
                }

                $panels['panels']['email']['lines'][] = sprintf(
                    '<div ' .
                        'style="clear: both; height: 46px; ' .
                        'padding-top: 5px; margin-bottom: 4px; overflow: hidden;%6$s">' .
                        '<span style="display: block; width: 80px; float: left;">%5$s</span> ' .
                        '<div class="project">%3$s</div>' .
                        '<a href="%4$s" target="_blank"><span class="title">%1$s</span></a></div>',
                    $overview->seen ?
                        h(iconv_mime_decode($overview->subject ?? '')) :
                        '<b>' . h(iconv_mime_decode($overview->subject ?? '')) . '</b>',
                    h($overview->msgno),
                    $emailLink,
                    sprintf(
                        'https://webmail.arhim.si/?_task=mail&_uid=%s&_mbox=INBOX&_action=show',
                        $overview->uid,
                    ),
                    $ArhintHelper->calendarDay($emailDate),
                    !empty($overview->seen) && $overview->seen == 1 ? '' : 'background-color: #cdffb0;',
                );
            }
        }

        $event->setResult(['panels' => $panels]);
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
                        $AttachmentsTable->save(
                            $attachment,
                            ['uploadedFilename' => [
                                (string)$attch['filename']->getClientFilename() => $attch['filename'],
                            ]],
                        );
                    }
                }

                $this->attachments = null;
            }
        }
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

        $attachmentLines = [
            'fs_attachments_start' => '<fieldset>',
            'fs_attachments_legend' => sprintf('<legend>%s</legend>', __('Archive')),
            'file.name.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.filename',
                    'options' => [
                        'type' => 'file',
                        'label' => false,
                    ],
                ],
            ],
            'file.document_id.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.document_id',
                    'options' => [
                        'type' => 'hidden',
                    ],
                ],
            ],
            'file.model.0' => [
                'method' => 'control',
                'parameters' => [
                    'field' => 'documents_attachments.0.model',
                    'options' => [
                        'type' => 'hidden',
                        'value' => 'Document',
                    ],
                ],
            ],
            'fs_attachments_end' => '</fieldset>',
        ];

        $view->Lil->insertIntoArray($formLines->form['lines'], $attachmentLines, ['before' => 'submit']);
    }
}
