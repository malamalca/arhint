<?php
declare(strict_types=1);

namespace LilInvoices\Lib;

use Cake\ORM\TableRegistry;
use Lil\Lib\Lil;

class LilInvoicesSidebar
{
    /**
     * setAdminSidebar method
     *
     * Add admin sidebar elements.
     *
     * @param mixed $event Event object.
     * @param \ArrayObject $sidebar Sidebar data.
     * @return void
     */
    public static function setAdminSidebar($event, $sidebar)
    {
        if (!$event->getSubject() instanceof \App\Controller\AppController) {
            return;
        }

        $controller = $event->getSubject();
        $request = $event->getSubject()->getRequest();
        $currentUser = $event->getSubject()->getCurrentUser();

        if (empty($currentUser)) {
            return;
        }

        if (empty($sidebar['documents'])) {
            // there is no "ACCOUNTING SIDEBAR"
            $invoices['title'] = __d('lil_invoices', 'Invoices');
            $invoices['visible'] = !empty($currentUser);
            $invoices['active'] = $request->getParam('plugin') == 'LilInvoices';
            $invoices['url'] = [
                'plugin' => 'LilInvoices',
                'controller' => 'Invoices',
                'action' => 'index',
            ];
            $invoices['items'] = [];

            // insert into sidebar right after welcome panel
            Lil::insertIntoArray($sidebar, ['documents' => $invoices], ['after' => 'welcome']);
        } else {
            $sidebar['documents']['active'] = $sidebar['documents']['active'] ||
                $request->getParam('plugin') == 'LilInvoices';
        }

        ////////////////////////////////////////////////////////////////////////////////////////
        if (empty($sidebar['documents']['items']['reports'])) {
            $sidebar['documents']['items']['reports'] = [
                'visible' => true,
                'title' => __d('lil_invoices', 'Reports'),
                'url' => false,
                'params' => [],
                'active' => $request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'report',
                'submenu' => [],
            ];
        }

        $sidebar['documents']['items']['reports']['submenu']['invoices_report'] = [
            'visible' => true,
            'title' => __d('lil_invoices', 'List'),
            'url' => [
                'plugin' => 'LilInvoices',
                'controller' => 'Invoices',
                'action' => 'report',
            ],
            'active' => $request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'report',
        ];

        ////////////////////////////////////////////////////////////////////////////////////////
        Lil::insertIntoArray(
            $sidebar['documents']['items'],
            [
                'received' => [
                    'visible' => true,
                    'title' => __d('lil_invoices', 'Received Documents'),
                    'url' => false,
                    'active' => false,
                ],
                'issued' => [
                    'visible' => true,
                    'title' => __d('lil_invoices', 'Issued Documents'),
                    'url' => false,
                    'active' => false,
                ],
            ],
            ['before' => 'reports']
        );

        // LOOKUPS SIDEBAR SUBMENU
        if (empty($sidebar['documents']['items']['lookups'])) {
            $sidebar['documents']['items']['lookups'] = [
                'visible' => true,
                'title' => __d('lil_invoices', 'Lookups'),
                'url' => false,
                'params' => [],
                'active' => false,
                'submenu' => [],
            ];
        }

        $sidebar['documents']['items']['lookups']['active'] =
            in_array($request->getParam('controller'), ['Items', 'InvoicesCounters']) ||
            ($request->getParam('controller') == 'Vats' &&
                        in_array($request->getParam('action'), ['index', 'edit'])) ||
            ($request->getParam('controller') == 'InvoicesTemplates' &&
                        in_array($request->getParam('action'), ['index', 'edit'])) ||
            ($request->getParam('controller') == 'Items' &&
                        in_array($request->getParam('action'), ['index', 'edit']));

        $sidebar['documents']['items']['lookups']['submenu'] =
            [
                'invoices_items' => [
                    'visible' => true,
                    'title' => __d('lil_invoices', 'Items'),
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'Items',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'Items',
                ],
                'invoices_counter' => [
                    'visible' => true,
                    'title' => __d('lil_invoices', 'Invoices Counters'),
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'InvoicesCounters',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'InvoicesCounters',
                ],
                'invoices_vat' => [
                    'visible' => true,
                    'title' => __d('lil_invoices', 'VAT levels'),
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'Vats',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'Vats' &&
                        in_array($request->getParam('action'), ['index', 'edit']),
                ],
                'invoices_templates' => [
                    'visible' => true,
                    'title' => __d('lil_invoices', 'Templates'),
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'InvoicesTemplates',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'InvoicesTemplates' &&
                        in_array($request->getParam('action'), ['index', 'edit']),
                ],
            ];

        // build counters submenu only when needed
        if (($request->getParam('plugin') == 'LilInvoices')) {
            ////////////////////////////////////////////////////////////////////////////////////////
            // Fetch counters
            //if (!$counters = Cache::read('LilInvoices.sidebarCounters', 'Lil')) {
            $InvoicesCounters = TableRegistry::get('LilInvoices.InvoicesCounters');
            $counters = $controller->Authorization->applyScope($InvoicesCounters->find(), 'index')
                ->where(['active' => true])
                ->order(['active', 'kind DESC', 'title'])
                ->all();
            //  Cache::write('LilInvoices.sidebarCounters', $counters, 'Lil');
            //}

            // determine current counter
            $isActionIndex = $request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'index';
            $currentCounter = $request->getQuery('counter');
            if (!$currentCounter) {
                $currentCounter = $request->getQuery('filter.counter');
            }
            if (!$currentCounter) {
                $currentCounter = $event->getSubject()->viewBuilder()->getVar('currentCounter');
            }
            if (!$currentCounter && $counters->count() > 0 && $isActionIndex) {
                $currentCounter = $counters->first()->id;
            }

            // build submenus
            foreach ($counters as $i => $c) {
                if ($currentCounter == $c->id) {
                    $sidebar['documents']['items'][$c->kind]['active'] = true;
                }

                $sidebar['documents']['items'][$c->kind]['submenu'][$c->id] = [
                    'visible' => true,
                    'title' => $c->title,
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'Invoices',
                        'action' => 'index',
                        '?' => ['counter' => $c->id],
                    ],
                    'active' => $currentCounter == $c->id,
                ];
            }
        }

        $event->setResult(['sidebar' => $sidebar]);
    }
}
