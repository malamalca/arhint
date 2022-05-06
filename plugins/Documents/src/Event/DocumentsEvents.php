<?php
declare(strict_types=1);

namespace Documents\Event;

use Cake\Event\EventListenerInterface;
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
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Lil.Panels.Crm.Contacts.view' => 'showDocumentsTable',
            'Lil.Panels.Projects.Projects.view' => 'showDocumentsTable',
        ];
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
        /*$view = $event->getSubject();
        $view->loadHelper('Paginator');
        $identity = $view->getRequest()->getAttribute('identity');

        // fetch counters
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
        $countersQuery = $DocumentsCounters->find();
        $identity->applyScope('index', $countersQuery);
        $counters = $countersQuery
            ->select(['id', 'title', 'direction', 'active'])
            ->order(['direction', 'title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        if (count($counters) == 0) {
            return $panels;
        }

        // fetch invoices
        $invoicesPerPage = 5;
        $page = (int)$view->getRequest()->getQuery('invoices.page', 1);

        // prepare query
        $sort = 'Invoices.';
        $sort .= $view->getRequest()->getQuery('invoices.sort', 'dat_issue');
        $sort .= ' ' . $view->getRequest()->getQuery('invoices.direction', 'DESC');

        $conditions = [];
        switch ($event->getName()) {
            case 'Lil.Panels.Crm.Contacts.view':
                $matchingDocuments = TableRegistry::getTableLocator()->get('Documents.DocumentsClients')->query()
                    ->select(['document_id'])
                    ->distinct()
                    ->where(['contact_id' => $panels->entity->id]);
                $conditions['id IN'] = $matchingDocuments;
                break;
            case 'Lil.Panels.Projects.Projects.view':
                $conditions['Invoices.project_id'] = $panels->entity->id;
                break;
        }

        $InvoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        // fetch invoices
        $query = $InvoicesTable->find();
        $invoices = $query
            ->select(['id', 'no', 'counter_id', 'title', 'dat_issue', 'total', 'attachments_count'])
            ->where($conditions)
            ->order($sort)
            ->limit($invoicesPerPage)
            ->page($page)
            ->all();

        // calculate total sum and number of invoices
        $query = $InvoicesTable->find();
        $invoicesTotals = $query
            ->select([
                'invoicesSum' => $query->func()->sum('Invoices.total'),
                'invoicesCount' => $query->func()->count('Invoices.id'),
            ])
            ->where($conditions)
            ->disableHydration()
            ->first();

        // set view variables
        $view->set('entityId', $view->getRequest()->getParam('pass.0'));
        $view->set('invoicesSum', $invoicesTotals['invoicesSum']);

        // set paging data
        $view->setRequest($view->getRequest()->withAttribute(
            'paging',
            ['Invoices' => [
                'pageCount' => (int)(ceil($invoicesTotals['invoicesCount'] / $invoicesPerPage)),
                'page' => $page,
                'scope' => 'invoices',
            ]]
        ));

        // create Lil panels
        switch ($view->getRequest()->getParam('plugin')) {
            case 'Projects':
                $panels->menu['add_invoice'] = [
                    'title' => __d('documents', 'Add Invoice'),
                    'visible' => true,
                    'submenu' => [],
                ];
                foreach ($counters as $counter) {
                    if ($counter->active) {
                        $panels->menu['add_invoice']['submenu'][] = [
                            'title' => $counter->title,
                            'url' => [
                                'plugin' => 'Documents',
                                'controller' => 'Invoices',
                                'action' => 'edit',
                                '?' => [
                                    'counter' => $counter->id,
                                    'project' => $view->getRequest()->getParam('pass.0'),
                                    'redirect' => base64_encode(Router::url(null, true)),
                                ],
                            ],
                        ];
                    }
                }

                if ($view->getRequest()->getQuery('tab') == 'invoices') {
                    $elementTemplate = 'Documents.invoices_projects_list';
                }

                // add documents table
                $invoicesTab = sprintf(
                    '<li class="tab col"><a href="%1$s" target="_self"%3$s>%2$s</a></li>',
                    $view->Url->build([$view->getRequest()->getParam('pass.0'), '?' => ['tab' => 'invoices']]),
                    __d('documents', 'Invoices'),
                    $view->getRequest()->getQuery('tab') == 'invoices' ? ' class="active"' : ''
                );

                $view->Lil->insertIntoArray(
                    $panels->panels['tabs']['lines'],
                    ['invoices' => $invoicesTab],
                    ['before' => 'post']
                );

                break;
            default:
                $elementTemplate = 'Documents.invoices_list';
        }

        if (!empty($elementTemplate)) {
            $invoicesPanels = [
                'invoices_table' => $view->element(
                    $elementTemplate,
                    ['invoices' => $invoices, 'counters' => $counters]
                ),
            ];

            $view->Lil->insertIntoArray($panels->panels, $invoicesPanels);
        }

        return $panels;*/

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

        if ($view->getRequest()->getQuery('tab') == 'invoices') {
            // invoices tab panel
            $invoicesPanels = [
                'invoices_table' => '<div id="tab-content-invoices"></div>',
            ];

            $view->Lil->insertIntoArray($panels->panels, $invoicesPanels);

            $url = Router::url(['plugin' => 'Documents', 'controller' => 'Invoices', 'action' => 'list', '_ext' => 'aht', '?' => $params]);
            $view->Lil->jsReady('$.get("' . $url . '", function(data) { $("#tab-content-invoices").html(data); });');
        }

        if ($view->getRequest()->getQuery('tab') == 'documents') {
            // documents tab panel
            $documentsPanels = [
                'documents_table' => '<div id="tab-content-documents"></div>',
            ];

            $view->Lil->insertIntoArray($panels->panels, $documentsPanels);

            $url = Router::url(['plugin' => 'Documents', 'controller' => 'Documents', 'action' => 'list', '_ext' => 'aht', '?' => $params]);
            $view->Lil->jsReady('$.get("' . $url . '", function(data) { $("#tab-content-documents").html(data); });');
        }

        return $panels;
    }
}
