<?php
declare(strict_types=1);

namespace Documents\Event;

use App\Model\Table\AttachmentsTable;
use App\View\Helper\ArhintHelper;
use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Event\EventManager;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\View\View;
use Documents\Lib\DocumentsSidebar;
use Documents\Lib\FursXml;
use Documents\Model\Entity\Invoice;
use Exception;
use Malamalca\FiscalPHP\FiscalQr;
use Throwable;

class DocumentsEvents implements EventListenerInterface
{
    /**
     * List of implemented events
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            //'Controller.initialize' => 'enableClientEditing',
            'App.HeartBeat.hourlyEmail' => 'hourlyEmail',
            'App.dashboard' => 'dashboardPanels',
            'View.beforeRender' => 'addScripts',
            'App.Sidebar.beforeRender' => 'modifySidebar',
            'App.Panels.Crm.Contacts.view' => 'showDocumentsTable',
            'App.Panels.Projects.Projects.view' => 'showDocumentsTable',
            'Model.afterSave' => 'updateAttachmentsCounter',
            'Model.afterDelete' => 'updateAttachmentsCounter',
            'Documents.Invoices.Export.Html' => 'showTaxBlock',
        ];
    }

    /**
     * Add tax confirmation block (QR, ZOI, EOR) to the invoice html before it is converted to PDF.
     *
     * @param \Cake\Event\Event $event Event object, subject is the invoice.
     * @param string $html Invoice html.
     * @return string
     */
    public function showTaxBlock(Event $event, string $html): string
    {
        $invoice = $event->getSubject();
        if (!$invoice instanceof Invoice || empty($invoice->id)) {
            return $html;
        }

        /** @var \Documents\Model\Table\InvoicesTaxConfirmationsTable $Confirmations */
        $Confirmations = TableRegistry::getTableLocator()->get('Documents.InvoicesTaxConfirmations');
        $confirmation = $Confirmations->findForInvoice($invoice->id);
        if (!$confirmation || !$confirmation->isConfirmed()) {
            return $html;
        }

        try {
            $qr = base64_encode(FiscalQr::png(
                (string)$confirmation->zoi,
                (string)$confirmation->issuer_taxno,
                FursXml::localTime($confirmation->issued_at),
            ));
        } catch (Throwable $e) {
            return $html;
        }

        // FURS: QR symbol has to be printed at least 12 x 12 mm (image is 16.764 mm with the quiet zone)
        $block = sprintf(
            '<div class="tax-confirmation" style="display: block; margin-top: 19mm; page-break-inside: avoid;">' .
            '<div style="display: block;"><img src="data:image/png;base64,%1$s" ' .
            'style="width: 16.764mm; height: 16.764mm;" /></div>' .
            '<div style="display: block; font-size: 6pt; line-height: 1.3; white-space: nowrap;">%2$s: %3$s</div>' .
            '<div style="display: block; font-size: 6pt; line-height: 1.3; white-space: nowrap;">%4$s: %5$s</div>' .
            '</div>',
            $qr,
            __d('documents', 'ZOI'),
            h($confirmation->zoi),
            __d('documents', 'EOR'),
            h($confirmation->eor),
        );

        // the default template reserves a slot next to the invoice data (currency, place and date of issue)
        $slot = '<div id="tax-block-slot" style="float: right; width: 28%;"></div>';
        if (str_contains($html, $slot)) {
            return str_replace($slot, str_replace('></div>', '>', $slot) . $block . '</div>', $html);
        }

        // custom templates without the slot: end of the content, above the footer
        $footerPos = strpos($html, '<div id="footer1"');
        if ($footerPos !== false) {
            return substr_replace($html, $block, $footerPos, 0);
        }

        $index = strripos($html, '</body>');
        if ($index === false) {
            return $html . $block;
        }

        return substr_replace($html, $block . '</body>', $index, strlen('</body>'));
    }

    /**
     * Hourly
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function hourlyEmail(Event $event): void
    {
        $panels = $event->getData('panels');

        /** @var \App\Model\Entity\User $user */
        $user = $event->getData('user');

        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $newDocuments = $DocumentsTable->find()
            ->select()
            ->contain(['Projects'])
            ->where([
                'Documents.owner_id' => $user->company_id,
                'Documents.created >' => (new DateTime())->addHours(-1),
            ])
            ->orderBy(['Documents.created'])
            ->all();

        if (!$newDocuments->isEmpty()) {
            $panels['panels']['documents'] = ['lines' => [
                '<h2>' . __d('documents', 'New Documents In Last Hour') . '</h2>',
            ]];

            foreach ($newDocuments as $document) {
                $panels['panels']['documents']['lines'][] = sprintf(
                    '<div><a href="%4$s">%2$s :: <span class="title">%1$s</span></a>%3$s</div>',
                    h($document->title),
                    h($document->no),
                    $document->project ?
                        sprintf(' <span class="project">[%s]</span>', h($document->project->title)) :
                        '',
                    Router::url([
                        'plugin' => 'Documents',
                        'controller' => 'Documents',
                        'action' => 'view',
                        $document->id,
                    ], true),
                );
            }
        }

        $event->setResult(['panels' => $panels] + $event->getData());
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

        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $newDocumentsQuery = $controller->Authorization->applyScope($DocumentsTable->find(), 'index')
            ->select(['id', 'no', 'dat_issue', 'title', 'project_id'])
            ->orderBy(['Documents.created DESC'])
            ->limit(6);

        $event = new Event('Documents.Dashboard.queryDocuments', $controller, [$newDocumentsQuery]);
        EventManager::instance()->dispatch($event);

        try {
            $newDocuments = $newDocumentsQuery->all();
        } catch (Exception $e) {
            return;
        }

        if (!$newDocuments->isEmpty()) {
            $panels['panels']['documents'] = [
                'params' => ['class' => 'dashboard-panel'],
                'lines' => [
                    '<h5>' . __d('documents', 'Last 6 documents') . '</h5>',
                ],
            ];

            $ArhintHelper = new ArhintHelper(new View());

            foreach ($newDocuments as $document) {
                $panels['panels']['documents']['lines'][] = sprintf(
                    '<div style="clear: both; height: 46px; margin-bottom: 10px; overflow: hidden;">' .
                        '<span style="display: block; width: 80px; float: left;">%5$s</span> ' .
                        '<div class="project">%3$s</div>' .
                        '<a href="%4$s"><span class="title">%1$s</span></a> %2$s</div>',
                    h($document->title),
                    h($document->no),
                    $document->project ? h($document->project->title) : '-',
                    Router::url([
                        'plugin' => 'Documents',
                        'controller' => 'Documents',
                        'action' => 'view',
                        $document->id,
                    ], true),
                    $document->dat_issue ? $ArhintHelper->calendarDay($document->dat_issue) : '',
                );
            }
        }

        $event->setResult(['panels' => $panels]);
    }

    /**
     * Add plugins css file to global layout.
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('Documents.documents');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Documents') {
            $view->set('admin_title', __d('documents', 'Documents'));
        }
    }

    /**
     * Modify sidebar
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $sidebar Sidebar object.
     * @return void
     */
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        DocumentsSidebar::setAdminSidebar($event, $sidebar);
    }

    /**
     * Add documents list to Contact and Project view page.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param mixed $panels Panels object.
     * @return void
     */
    public function showDocumentsTable(Event $event, mixed $panels): void
    {
        /** @var \App\View\AppView $view */
        $view = $event->getSubject();

        $invoicesTab = sprintf(
            '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
            $view->Url->build([$view->getRequest()->getParam('pass.0'), '?' => ['tab' => 'invoices']]),
            __d('documents', 'Invoices'),
            $view->getRequest()->getQuery('tab') == 'invoices' ? ' class="active"' : '',
        );

        $documentsTab = sprintf(
            '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
            $view->Url->build([$view->getRequest()->getParam('pass.0'), '?' => ['tab' => 'documents']]),
            __d('documents', 'Documents'),
            $view->getRequest()->getQuery('tab') == 'documents' ? ' class="active"' : '',
        );

        $view->Lil->insertIntoArray(
            $panels->panels['tabs']['lines'],
            ['documents' => $documentsTab, 'invoices' => $invoicesTab],
            ['before' => 'post'],
        );

        //$url = $view->getRequest()->getRequestTarget();
        $sourceRequest = Router::reverseToArray($view->getRequest());
        unset($sourceRequest['?']['page']);
        unset($sourceRequest['?']['sort']);
        unset($sourceRequest['?']['direction']);

        $url = Router::normalize($sourceRequest);
        $params = [
            'source' => $url,
            'page' => $view->getRequest()->getQuery('page'),
            'sort' => $view->getRequest()->getQuery('sort'),
            'direction' => $view->getRequest()->getQuery('direction'),
        ];

        $activeTab = $view->getRequest()->getQuery('tab', $view->get('tab'));

        if ($activeTab == 'invoices') {
            // invoices tab panel
            $invoicesPanels = [
                'invoices_table' => '<div id="tab-content-invoices"></div>',
            ];

            $view->Lil->insertIntoArray($panels->panels, $invoicesPanels);

            $url = Router::url([
                'plugin' => 'Documents',
                'controller' => 'Invoices',
                'action' => 'list',
                '_ext' => 'aht',
                '?' => $params,
            ]);
            $view->Lil->jsReady('$.get("' . $url . '", function(data) { $("#tab-content-invoices").html(data); });');
        }

        if ($activeTab == 'documents') {
            // documents tab panel
            $documentsPanels = [
                'documents_table' => '<div id="tab-content-documents"></div>',
            ];

            $view->Lil->insertIntoArray($panels->panels, $documentsPanels);

            $url = Router::url([
                'plugin' => 'Documents',
                'controller' => 'Documents',
                'action' => 'list',
                '_ext' => 'aht',
                '?' => $params,
            ]);
            $view->Lil->jsReady('$.get("' . $url . '", function(data) { $("#tab-content-documents").html(data); });');
        }

        //return $panels;
        $event->setResult($panels);
    }

    /**
     * Update attachments count
     *
     * @param \Cake\Event\Event $event Event object
     * @param \Cake\Datasource\EntityInterface $entity Entity object
     * @param \ArrayObject $options Options array
     * @return void
     */
    public function updateAttachmentsCounter(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (get_class($event->getSubject()) == AttachmentsTable::class) {
            /** @var \App\Model\Table\AttachmentsTable $attachmentsTable */
            $attachmentsTable = $event->getSubject();

            /** @var \App\Model\Entity\Attachment $entity */

            if (in_array($entity->model, ['Invoice', 'Document'])) {
                try {
                    // count attachments for that model + foreign id
                    $count = $attachmentsTable->find()
                        ->where([
                            'model' => $entity->model,
                            'foreign_id' => $entity->foreign_id,
                        ])
                        ->count();

                    if ($entity->model == 'Invoice') {
                        $targetTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
                    } else {
                        $targetTable = TableRegistry::getTableLocator()->get('Documents.Documents');
                    }

                    // update attachments_count on the parent record
                    $targetTable->updateQuery()
                        ->set(['attachments_count' => $count])
                        ->where(['id' => $entity->foreign_id])
                        ->execute();
                } catch (Throwable $e) {
                    // silently ignore errors to avoid breaking the saved flow
                    die;
                }
            }
        }
    }
}
