<?php
declare(strict_types=1);

namespace Expenses\Lib;

use App\AppPluginsEnum;
use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;

class ExpensesSidebar
{
    /**
     * Add admin sidebar elements.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $sidebar Sidebar data.
     * @return void
     */
    public static function setAdminSidebar(Event $event, ArrayObject $sidebar): void
    {
        if (!$event->getSubject() instanceof AppController) {
            return;
        }

        /** @var \App\Controller\AppController $controller */
        $controller = $event->getSubject();
        if (!$controller->hasCurrentUser()) {
            return;
        }

        if (!$controller->getCurrentUser()->hasAccess(AppPluginsEnum::Expenses)) {
            return;
        }

        $request = $controller->getRequest();

        $accounting['title'] = __d('expenses', 'Accounting');
        $accounting['visible'] = true;
        $accounting['active'] = $request->getParam('plugin') == 'Expenses';
        $accounting['url'] = [
            'plugin' => 'Expenses',
            'controller' => 'Payments',
            'action' => 'index',
        ];
        $accounting['items'] = [
            'payments' => [
                'title' => __d('expenses', 'Payments'),
                'visible' => true,
                'url' => [
                    'plugin' => 'Expenses',
                    'controller' => 'Payments',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'Payments',
            ],
            'expenses' => [
                'title' => __d('expenses', 'Expenses'),
                'visible' => true,
                'url' => [
                    'plugin' => 'Expenses',
                    'controller' => 'Expenses',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'Expenses' &&
                    !in_array($request->getParam('action'), ['reportUnpaid', 'graphYeary', 'importSepa']),
            ],
            'reports' => [
                'visible' => true,
                'title' => __d('expenses', 'Reports'),
                'url' => false,
                'expandable' => true,
                'params' => [],
                'active' => ($request->getParam('controller') == 'Expenses') &&
                    (in_array(substr($request->getParam('action'), 0, 5), ['repor', 'graph'])),
                'expand' => ($request->getParam('controller') == 'Expenses') &&
                    (in_array(substr($request->getParam('action'), 0, 5), ['repor', 'graph'])),
                'submenu' => [
                    'payments_accounts' => [
                        'visible' => true,
                        'title' => __d('expenses', 'Unpaid Invoices'),

                        'url' => [
                            'plugin' => 'Expenses',
                            'controller' => 'Expenses',
                            'action' => 'reportUnpaid',
                        ],
                        'active' => $request->getParam('controller') == 'Expenses' &&
                        $request->getParam('action') == 'reportUnpaid',
                    ],
                    'graph_expenses' => [
                        'visible' => true,
                        'title' => __d('expenses', 'Expenses Graph'),

                        'url' => [
                            'plugin' => 'Expenses',
                            'controller' => 'Expenses',
                            'action' => 'graphExpenses',
                        ],
                        'active' => $request->getParam('controller') == 'Expenses' &&
                        $request->getParam('action') == 'graphExpenses',
                    ],
                    'graph_yearly' => [
                        'visible' => true,
                        'title' => __d('expenses', 'Yearly Graph'),

                        'url' => [
                            'plugin' => 'Expenses',
                            'controller' => 'Expenses',
                            'action' => 'graphYearly',
                        ],
                        'active' => $request->getParam('controller') == 'Expenses' &&
                        $request->getParam('action') == 'graphYearly',
                    ],
                ],
            ],
            'import' => [
                'visible' => true,
                'title' => __d('expenses', 'Import'),
                'url' => false,
                'expandable' => true,
                'params' => [],
                'active' => ($request->getParam('controller') == 'Expenses') &&
                    (substr($request->getParam('action'), 0, 6) == 'import'),
                'expand' => ($request->getParam('controller') == 'Expenses') &&
                    (substr($request->getParam('action'), 0, 6) == 'import'),
                'submenu' => [
                    'sepaxml' => [
                        'visible' => true,
                        'title' => __d('expenses', 'Sepa XML'),

                        'url' => [
                            'plugin' => 'Expenses',
                            'controller' => 'Expenses',
                            'action' => 'importSepa',
                        ],
                        'active' => $request->getParam('controller') == 'Expenses' &&
                        $request->getParam('action') == 'importSepa',
                    ],
                ],
            ],
            'lookups' => [
                'visible' => true,
                'title' => __d('expenses', 'Lookups'),
                'url' => false,
                'expandable' => true,
                'active' => in_array($request->getParam('controller'), ['PaymentsAccounts']),
                'expand' => in_array($request->getParam('controller'), ['PaymentsAccounts']),
                'submenu' => [
                    'payments_accounts' => [
                        'visible' => true,
                        'title' => __d('expenses', 'Accounts'),

                        'url' => [
                            'plugin' => 'Expenses',
                            'controller' => 'PaymentsAccounts',
                            'action' => 'index',
                        ],
                        'active' => $request->getParam('controller') == 'PaymentsAccounts',
                    ],
                ],
            ],
        ];

        $sidebar->append($accounting);

        $event->setResult(['sidebar' => $sidebar]);
    }
}
