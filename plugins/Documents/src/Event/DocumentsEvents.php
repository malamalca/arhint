<?php
declare(strict_types=1);

namespace Documents\Event;

use Cake\Event\EventListenerInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Documents\Lib\DocumentsSidebar;

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
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Lil.Panels.Crm.Contacts.view' => 'showDocumentsTable',
            'Lil.Panels.Projects.Projects.view' => 'showDocumentsTable',
        ];
    }

    /**
     * Hourly
     *
     * @param \Cake\Event\Event $event Event object.
     * @return void
     */
    public function hourlyEmail($event)
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
                'Documents.created >' => (new FrozenTime())->addHours(-1),
            ])
            ->order(['Documents.created'])
            ->all();

        if (!$newDocuments->isEmpty()) {
            $panels['panels']['documents'] = ['lines' => [
                '<h2>' . __d('documents', 'New Documents In Last Hour') . '</h2>',
            ]];

            foreach ($newDocuments as $document) {
                $panels['panels']['documents']['lines'][] = sprintf(
                    '<div><a href="%4$s">%2$s :: <span class="title big">%1$s</span></a>%3$s</div>',
                    h($document->title),
                    h($document->no),
                    $document->project ?
                        sprintf(' <span class="project small light">[%s]</span>', h($document->project->title)) :
                        '',
                    Router::url([
                        'plugin' => 'Documents',
                        'controller' => 'Documents',
                        'action' => 'view',
                        $document->id,
                    ], true)
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
    public function dashboardPanels($event, $panels)
    {
        $view = $event->getSubject();

        /** @var \App\Model\Entity\User $user */
        $user = $view->getCurrentUser();

        $DocumentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');
        $newDocuments = $DocumentsTable->find()
            ->select()
            ->contain(['Projects'])
            ->where([
                'Documents.owner_id' => $user->company_id,
            ])
            ->order(['Documents.created DESC'])
            ->limit(6)
            ->all();

        if (!$newDocuments->isEmpty()) {
            $panels['panels']['documents'] = ['lines' => [
                '<h5>' . __d('documents', 'Last 6 documents') . '</h5>',
            ]];

            foreach ($newDocuments as $document) {
                $panels['panels']['documents']['lines'][] = sprintf(
                    '<div><span style="display: block; width: 80px; float: left">%5$s</span> ' .
                        '<a href="%4$s"><span class="title big">%1$s</span></a> %2$s %3$s</div>',
                    h($document->title),
                    h($document->no),
                    $document->project ?
                        sprintf(' <span class="project small light">[%s]</span>', h($document->project->title)) :
                        '',
                    Router::url([
                        'plugin' => 'Documents',
                        'controller' => 'Documents',
                        'action' => 'view',
                        $document->id,
                    ], true),
                    (string)$document->dat_issue
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
    public function addScripts($event)
    {
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
    public function modifySidebar($event, $sidebar)
    {
        DocumentsSidebar::setAdminSidebar($event, $sidebar);
    }

    /**
     * Add documents list to Contact and Project view page.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Lil\Lib\LilPanels $panels Panels object.
     * @return \Lil\Lib\LilPanels
     */
    public function showDocumentsTable($event, $panels)
    {
        $view = $event->getSubject();

        $invoicesTab = sprintf(
            '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
            $view->Url->build([$view->getRequest()->getParam('pass.0'), '?' => ['tab' => 'invoices']]),
            __d('documents', 'Invoices'),
            $view->getRequest()->getQuery('tab') == 'invoices' ? ' class="active"' : ''
        );

        $documentsTab = sprintf(
            '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
            $view->Url->build([$view->getRequest()->getParam('pass.0'), '?' => ['tab' => 'documents']]),
            __d('documents', 'Documents'),
            $view->getRequest()->getQuery('tab') == 'documents' ? ' class="active"' : ''
        );

        $view->Lil->insertIntoArray(
            $panels->panels['tabs']['lines'],
            ['documents' => $documentsTab, 'invoices' => $invoicesTab],
            ['before' => 'post']
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

        return $panels;
    }
}
