<?php
declare(strict_types=1);

namespace Documents\Lib;

use Cake\ORM\TableRegistry;
use Lil\Lib\Lil;

class DocumentsSidebar
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

        $documents['title'] = __d('documents', 'Documents');
        $documents['visible'] = true;
        $documents['active'] = $request->getParam('plugin') == 'Documents';
        $documents['url'] = [
            'plugin' => 'Documents',
            'controller' => 'Invoices',
            'action' => 'index',
        ];
        $documents['items'] = [];

        $sidebar['documents'] = $documents;

        ////////////////////////////////////////////////////////////////////////////////////////
        if (empty($sidebar['documents']['items']['reports'])) {
            $sidebar['documents']['items']['reports'] = [
                'visible' => true,
                'title' => __d('documents', 'Reports'),
                'url' => false,
                'params' => [],
                'active' => $request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'report',
                'submenu' => [],
            ];
        }

        $sidebar['documents']['items']['reports']['submenu']['documents_report'] = [
            'visible' => true,
            'title' => __d('documents', 'List'),
            'url' => [
                'plugin' => 'Documents',
                'controller' => 'Invoices',
                'action' => 'report',
            ],
            'active' => $request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'report',
        ];

        ////////////////////////////////////////////////////////////////////////////////////////
        Lil::insertIntoArray(
            $sidebar['documents']['items'],
            [
                'documents' => [
                    'visible' => true,
                    'title' => __d('documents', 'Documents'),
                    'url' => null,
                    'active' => false,
                ],
                'invoices' => [
                    'visible' => true,
                    'title' => __d('documents', 'Invoices'),
                    'url' => null,
                    'active' => false,
                ],
                'travelorders' => [
                    'visible' => true,
                    'title' => __d('documents', 'Travel Orders'),
                    'url' => null,
                    'active' => false,
                ],
                /*'received' => [
                    'visible' => true,
                    'title' => __d('documents', 'Received Documents'),
                    'url' => false,
                    'active' => false,
                ],
                'issued' => [
                    'visible' => true,
                    'title' => __d('documents', 'Issued Documents'),
                    'url' => false,
                    'active' => false,
                ],*/
            ],
            ['before' => 'reports']
        );

        // LOOKUPS SIDEBAR SUBMENU
        if (empty($sidebar['documents']['items']['lookups'])) {
            $sidebar['documents']['items']['lookups'] = [
                'visible' => true,
                'title' => __d('documents', 'Lookups'),
                'url' => false,
                'params' => [],
                'active' => false,
                'submenu' => [],
            ];
        }

        $sidebar['documents']['items']['lookups']['active'] =
            in_array($request->getParam('controller'), ['Items', 'DocumentsCounters']) ||
            ($request->getParam('controller') == 'Vats' &&
                        in_array($request->getParam('action'), ['index', 'edit'])) ||
            ($request->getParam('controller') == 'DocumentsTemplates' &&
                        in_array($request->getParam('action'), ['index', 'edit'])) ||
            ($request->getParam('controller') == 'Items' &&
                        in_array($request->getParam('action'), ['index', 'edit']));

        $sidebar['documents']['items']['lookups']['submenu'] =
            [
                'documents_items' => [
                    'visible' => true,
                    'title' => __d('documents', 'Items'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'Items',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'Items',
                ],
                'documents_counter' => [
                    'visible' => true,
                    'title' => __d('documents', 'Counters'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'DocumentsCounters',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'DocumentsCounters',
                ],
                'documents_vat' => [
                    'visible' => true,
                    'title' => __d('documents', 'VAT levels'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'Vats',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'Vats' &&
                        in_array($request->getParam('action'), ['index', 'edit']),
                ],
                'documents_templates' => [
                    'visible' => true,
                    'title' => __d('documents', 'Templates'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'DocumentsTemplates',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'DocumentsTemplates' &&
                        in_array($request->getParam('action'), ['index', 'edit']),
                ],
            ];

        // build counters submenu only when needed
        if (($request->getParam('plugin') == 'Documents')) {
            ////////////////////////////////////////////////////////////////////////////////////////
            // Fetch counters

            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $counters = $DocumentsCounters->rememberForUser(
                $currentUser->id,
                $controller->Authorization->applyScope($DocumentsCounters->find(), 'index')
            );

            // determine current counter
            $isActionIndex = $request->getParam('controller') == 'Invoices' && $request->getParam('action') == 'index';
            $currentCounter = $request->getQuery('counter');
            if (empty($currentCounter)) {
                $currentCounter = $request->getQuery('filter.counter');
            }
            if (empty($currentCounter)) {
                $currentCounter = $event->getSubject()->viewBuilder()->getVar('currentCounter');
            }
            if (empty($currentCounter) && $counters->count() > 0 && $isActionIndex) {
                $currentCounter = $counters->first()->id;
            }

            // build submenus
            foreach ($counters as $i => $c) {
                if ($currentCounter == $c->id) {
                    $sidebar['documents']['items'][$c->kind]['active'] = true;
                }

                switch ($c->kind) {
                    case 'invoices':
                        $controllerName = 'Invoices';
                        break;
                    case 'travelorders':
                        $controllerName = 'TravelOrders';
                        break;
                    default:
                        $controllerName = 'Invoices';
                }

                $sidebar['documents']['items'][$c->kind]['submenu'][$c->id] = [
                    'visible' => true,
                    'title' => $c->title,
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => $controllerName,
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
