<?php
declare(strict_types=1);

namespace Documents\Lib;

use App\Controller\AppController;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Documents\Model\Entity\DocumentsCounter;
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
    public static function setAdminSidebar(mixed $event, ArrayObject $sidebar): void
    {
        if (!$event->getSubject() instanceof AppController) {
            return;
        }

        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();
        if (!$controller->hasCurrentUser()) {
            return;
        }

        $request = $controller->getRequest();
        $currentUser = $controller->getCurrentUser();

        ////////////////////////////////////////////////////////////////////////////////////////
        // Fetch counters

        /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
        $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

        $counters = $DocumentsCounters->rememberForUser(
            $currentUser->id,
            $controller->Authorization->applyScope($DocumentsCounters->find(), 'index')
        );

        $documents['title'] = __d('documents', 'Documents');
        $documents['visible'] = true;
        $documents['active'] = $request->getParam('plugin') == 'Documents';
        $documents['url'] = [
            'plugin' => 'Documents',
            'controller' => 'Invoices',
            'action' => 'index',
        ];
        $documents['items'] = [];

        if (!$counters->isEmpty()) {
            if (get_class($counters->first()) == DocumentsCounter::class) {
                $documents['url']['controller'] = 'Documents';
                $documents['url']['?']['counter'] = $counters->first()->id;
            }
        }

        $sidebar['documents'] = $documents;

        ////////////////////////////////////////////////////////////////////////////////////////
        if (empty($sidebar['documents']['items']['reports'])) {
            $sidebar['documents']['items']['reports'] = [
                'visible' => $currentUser->hasRole('admin'),
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
            ],
            ['before' => 'reports']
        );

        // EXPORT SIDEBAR SUBMENU
        if (empty($sidebar['documents']['items']['exports'])) {
            $sidebar['documents']['items']['exports'] = [
                'visible' => true,
                'title' => __d('documents', 'Export'),
                'url' => false,
                'params' => [],
                'active' =>
                    $request->getParam('controller') == 'Invoices' &&
                    substr($request->getParam('action'), 0, 6) == 'export',
                'submenu' => [
                    'eRacuni' => [
                        'visible' => true,
                        'title' => __d('documents', 'eRacuni'),
                        'url' => [
                            'plugin' => 'Documents',
                            'controller' => 'Invoices',
                            'action' => 'export_eracuni',
                        ],
                        'active' => $request->getParam('controller') == 'Invoices',
                    ],
                ],
            ];
        }

        // LOOKUPS SIDEBAR SUBMENU
        if (empty($sidebar['documents']['items']['lookups'])) {
            $sidebar['documents']['items']['lookups'] = [
                'visible' => $currentUser->hasRole('admin'),
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
            ($request->getParam('controller') == 'Vehicles' &&
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
                'vehicles' => [
                    'visible' => true,
                    'title' => __d('documents', 'Vehicles'),
                    'url' => [
                        'plugin' => 'Documents',
                        'controller' => 'Vehicles',
                        'action' => 'index',
                    ],
                    'active' => $request->getParam('controller') == 'Vehicles' &&
                        in_array($request->getParam('action'), ['index', 'edit']),
                ],
            ];

        // build counters submenu only when needed
        if (($request->getParam('plugin') == 'Documents')) {
            // determine current counter
            $isActionIndex = $request->getParam('controller') == 'Documents' && $request->getParam('action') == 'index';
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
            foreach ($counters as $c) {
                if ($currentCounter == $c->id) {
                    $sidebar['documents']['items'][strtolower($c->kind)]['active'] = true;
                }

                $controllerName = $c->kind;

                $sidebar['documents']['items'][strtolower($c->kind)]['submenu'][$c->id] = [
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
