<?php
declare(strict_types=1);

namespace LilInvoices\Event;

use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use LilInvoices\Lib\LilInvoicesSidebar;

class LilInvoicesEvents implements EventListenerInterface
{
    /**
     * List of implemented events
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            //'Controller.initialize' => 'enableClientEditing',
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Lil.Panels.LilCrm.Contacts.view' => 'showInvoicesTable',
            'Lil.Panels.LilProjects.Projects.view' => 'showInvoicesTable',
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
        echo $view->Html->css('LilInvoices.lil_invoices');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'LilInvoices') {
            $view->set('admin_title', __d('lil_invoices', 'Invoices'));
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
        LilInvoicesSidebar::setAdminSidebar($event, $sidebar);
    }

    /**
     * Add invoices list to Contact and Project view page.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Lil\Lib\LilPanels $panels Panels object.
     * @return \Lil\Lib\LilPanels
     */
    public function showInvoicesTable($event, $panels)
    {
        $view = $event->getSubject();
        $view->loadHelper('Paginator');
        $identity = $view->getRequest()->getAttribute('identity');

        // fetch counters
        $InvoicesCounters = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesCounters');
        $countersQuery = $InvoicesCounters->find();
        $identity->applyScope('index', $countersQuery);
        $counters = $countersQuery
            ->select(['id', 'title', 'kind', 'active'])
            ->order(['kind', 'title'])
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        // fetch invoices
        $invoicesPerPage = 5;
        $page = (int)$view->getRequest()->getQuery('invoices.page', 1);

        // prepare query
        $sort = 'Invoices.';
        $sort .= $view->getRequest()->getQuery('invoices.sort', 'no');
        $sort .= ' ' . $view->getRequest()->getQuery('invoices.direction', 'DESC');

        $Invoices = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');
        $query = $Invoices->find();

        switch ($event->getName()) {
            case 'Lil.Panels.LilCrm.Contacts.view':
                $query->where([
                    'OR' => [
                        'Buyers.contact_id' => $panels->entity->id,
                        'Issuers.contact_id' => $panels->entity->id,
                    ],
                ])
                ->contain(['Buyers', 'Issuers']);
                break;
            case 'Lil.Panels.LilProjects.Projects.view':
                $query->where(['Invoices.project_id' => $panels->entity->id]);
                break;
        }

        // fetch invoices
        $query4Invoices = clone $query;
        $invoices = $query4Invoices->order($sort)
            ->limit($invoicesPerPage)
            ->page($page)
            ->all();

        // calculate total sum and number of invoices
        $invoicesTotals = $query->select([
            'invoicesSum' => $query->func()->sum('Invoices.total'),
            'invoicesCount' => $query->func()->count('Invoices.id'),
        ])
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
            case 'LilProjects':
                $panels->menu['add_invoice'] = [
                    'title' => __d('lil_invoices', 'Add Invoice'),
                    'visible' => true,
                    'submenu' => [],
                ];
                foreach ($counters as $counter) {
                    if ($counter->active) {
                        $panels->menu['add_invoice']['submenu'][] = [
                            'title' => $counter->title,
                            'url' => [
                                'plugin' => 'LilInvoices',
                                'controller' => 'Invoices',
                                'action' => 'add',
                                '?' => [
                                    'counter' => $counter->id,
                                    'project' => $view->getRequest()->getParam('pass.0'),
                                    'redirect' => base64_encode(Router::url(null, true)),
                                ],
                            ],
                        ];
                    }
                }
                $elementTemplate = 'LilInvoices.invoices_projects_list';
                break;
            default:
                $elementTemplate = 'LilInvoices.invoices_list';
        }
        $invoicesPanels = [
            'invoices_title' => '<h3>' . __d('lil_invoices', 'Invoices') . '</h3>',
            'invoices_table' => $view->element($elementTemplate, ['invoices' => $invoices, 'counters' => $counters]),
        ];

        $view->Lil->insertIntoArray($panels->panels, $invoicesPanels);

        return $panels;
    }
}
