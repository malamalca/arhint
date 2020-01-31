<?php
declare(strict_types=1);

namespace LilInvoices\Event;

use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;
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
     * Add invoices list to Contact view page.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $panels Panels object.
     * @return \ArrayObject
     */
    public function showInvoicesTable($event, $panels)
    {
        $view = $event->getSubject();
        $view->loadHelper('Paginator');

        $Invoices = TableRegistry::get('LilInvoices.Invoices');
        $invoices = $Invoices->find()
            ->where([
                'OR' => [
                    'Buyers.contact_id' => $panels['entity']->id,
                    'Issuers.contact_id' => $panels['entity']->id,
                ],
                // II - izdajatelj; IV - prejemnik; BY - naročnik
            ])
            ->contain(['Buyers', 'Issuers', 'InvoicesCounters'])
            ->order('Invoices.dat_issue DESC')
            ->all();

        $page = (int)$view->getRequest()->getQuery('invoices.page', 1);
        $view->setRequest($view->getRequest()->withParam(
            'paging',
            ['Invoices' => [
                'pageCount' => floor($invoices->count() / 10),
                'page' => $page,
                'scope' => 'invoices',
            ]]
        ));

        $invoicesPanels = [
            'payments_title' => '<h2>' . __d('lil_invoices', 'Invoices') . '</h2>',
            'payments_table' => $view->element('LilInvoices.invoices_list', [
                'invoices' => $invoices->take(10, ($page - 1 ) * 10),
            ]),
        ];

        $view->Lil->insertIntoArray($panels['panels'], $invoicesPanels);

        return $panels;
    }
}
