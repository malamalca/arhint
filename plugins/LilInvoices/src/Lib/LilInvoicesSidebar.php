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
                'expandable' => true,
                'params' => [],
                'active' => false,
                'expand' => false,
                'submenu' => [],
            ];
        }
            $sidebar['documents']['items']['reports']['expand'] =
                $exp = $sidebar['documents']['items']['reports']['expand'] ||
                ($request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'report');

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
                        'expand' => false,
                    ],
                    'issued' => [
                        'visible' => true,
                        'title' => __d('lil_invoices', 'Issued Documents'),
                        'url' => false,
                        'active' => false,
                        'expand' => false,
                    ],
                ],
                ['before' => 'reports']
            );

        // ARCHIVE SIDEBAR SUBMENU
        if (empty($sidebar['documents']['items']['archive'])) {
            $sidebar['documents']['items']['archive'] = [
            'visible' => true,
            'title' => __d('lil_invoices', 'Archive'),
            'url' => false,
            'expandable' => true,
            'params' => [],
            'active' => false,
            'expand' => false,
            'submenu' => [],
            ];
        }

            // LOOKUPS SIDEBAR SUBMENU
        if (empty($sidebar['documents']['items']['lookups'])) {
            $sidebar['documents']['items']['lookups'] = [
            'visible' => true,
            'title' => __d('lil_invoices', 'Lookups'),
            'url' => false,
            'expandable' => true,
            'params' => [],
            'active' => false,
            'expand' => false,
            'submenu' => [],
            ];
        }

        $sidebar['documents']['items']['lookups']['expand'] =
            $sidebar['documents']['items']['lookups']['expand'] ||
            in_array($request->getParam('controller'), ['Items', 'InvoicesCounters']) ||
            ($request->getParam('controller') == 'Vats' &&
                        in_array($request->getParam('action'), ['index', 'edit'])) ||
            ($request->getParam('controller') == 'InvoicesTemplates' &&
                        in_array($request->getParam('action'), ['index', 'edit'])) ||
            ($request->getParam('controller') == 'Items' &&
                        in_array($request->getParam('action'), ['index', 'edit']));

        $sidebar['documents']['items']['lookups']['active'] = $sidebar['documents']['items']['lookups']['expand'];

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
        if (($request->getParam('plugin') == 'LilInvoices') && ($request->getParam('controller') == 'Invoices')) {
            ////////////////////////////////////////////////////////////////////////////////////////
            // Fetch counters
            //if (!$counters = Cache::read('LilInvoices.sidebarCounters', 'Lil')) {
            $InvoicesCounters = TableRegistry::get('LilInvoices.InvoicesCounters');
            $counters = $InvoicesCounters
                ->find()
                ->where(['owner_id' => $currentUser['company_id']])
                ->order(['active', 'kind DESC', 'title'])
                ->all();
            //  Cache::write('LilInvoices.sidebarCounters', $counters, 'Lil');
            //}

            // determine counter_id for sidebar
            $activeCounter = null;
            if (
                !$request->getQuery('counter') &&
                $request->getParam('controller') == 'Invoices' &&
                in_array($request->getParam('action'), ['view', 'preview'])
            ) {
                $invCounter = TableRegistry::get('LilInvoices.Invoices')
                    ->find()
                    ->select(['counter_id'])
                    ->where(['Invoices.id' => $request->getParam('pass.0')])
                    ->first();

                $activeCounter = $invCounter->id;
            }

            // build submenus
            $archivedCounters = [];
            $activeCounters = [];
            foreach ($counters as $c) {
                if ($c->active) {
                    $target = $c->kind;
                    $activeCounters[$c->kind][] = $c->id;
                } else {
                    $target = 'archive';
                    $archivedCounters[] = $c->id;
                }
                $sidebar['documents']['items'][$target]['submenu'][$c->id] = [
                    'visible' => true,
                    'title' => $c->title,
                    'url' => [
                        'plugin' => 'LilInvoices',
                        'controller' => 'Invoices',
                        'action' => 'index',
                        '?' => ['counter' => $c->id],
                    ],
                    'active' => false,
                ];
            }

            // determine current counter
            $currentCounter = $request->getQuery('counter');
            if (!$currentCounter) {
                $currentCounter = $request->getQuery('filter.counter');
            }
            if (!$currentCounter && !empty($view->viewVars['currentCounter'])) {
                $currentCounter = $view->viewVars['currentCounter'];
            }

            if ($currentCounter) {
                $tgt = 'archive';
                foreach ($activeCounters as $activeCounterKind => $activeKindCounters) {
                    if (in_array($currentCounter, $activeKindCounters)) {
                        $tgt = $activeCounterKind;
                        break;
                    }
                }
            }

            if (!$currentCounter && !empty($activeCounters)) {
                $counterKinds = array_keys($activeCounters);

                if (in_array($request->getQuery('kind'), $counterKinds)) {
                    $tgt = $request->getQuery('kind');
                } else {
                    $tgt = reset($counterKinds);
                }

                if ($tgt) {
                    $currentCounter = reset($activeCounters[$tgt]);
                }
            }

            if (!$currentCounter && !empty($archivedCounters)) {
                $currentCounter = reset($archivedCounters);
                $targerSubmenu = 'archive';
            }

            if (
                !empty($tgt) &&
                ($request->getParam('plugin') == 'LilInvoices') &&
                !(substr($request->getParam('action'), 0, 6) == 'report')
            ) {
                $sidebar['documents']['items'][$tgt]['active'] = true;
                $sidebar['documents']['items'][$tgt]['submenu'][$currentCounter]['active'] = true;
            }
        }

        $event->setResult(['sidebar' => $sidebar]);
    }
}
