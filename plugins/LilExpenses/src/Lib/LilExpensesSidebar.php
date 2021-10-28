<?php
declare(strict_types=1);

namespace LilExpenses\Lib;

class LilExpensesSidebar
{
    /**
     * Add admin sidebar elements.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $sidebar Sidebar data.
     * @return bool|null
     */
    public static function setAdminSidebar($event, $sidebar)
    {
        if (!$event->getSubject() instanceof \App\Controller\AppController) {
            return false;
        }

        $request = $event->getSubject()->getRequest();
        $currentUser = $event->getSubject()->getCurrentUser();

        if (empty($currentUser) || !$currentUser->hasRole('admin')) {
            return false;
        }

        $accounting['title'] = __d('lil_expenses', 'Accounting');
        $accounting['visible'] = !empty($currentUser);
        $accounting['active'] = $request->getParam('plugin') == 'LilExpenses';
        $accounting['url'] = [
            'plugin' => 'LilExpenses',
            'controller' => 'Payments',
            'action' => 'index',
        ];
        $accounting['items'] = [
            'payments' => [
                'title' => __d('lil_expenses', 'Payments'),
                'visible' => true,
                'url' => [
                    'plugin' => 'LilExpenses',
                    'controller' => 'Payments',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'Payments',
            ],
            'expenses' => [
                'title' => __d('lil_expenses', 'Expenses'),
                'visible' => true,
                'url' => [
                    'plugin' => 'LilExpenses',
                    'controller' => 'Expenses',
                    'action' => 'index',
                ],
                'active' => $request->getParam('controller') == 'Expenses' &&
                    !in_array($request->getParam('action'), ['reportUnpaid', 'graphYeary', 'importSepa']),
            ],
            'reports' => [
                'visible' => true,
                'title' => __d('lil_expenses', 'Reports'),
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
                        'title' => __d('lil_expenses', 'Unpaid Invoices'),

                        'url' => [
                            'plugin' => 'LilExpenses',
                            'controller' => 'Expenses',
                            'action' => 'reportUnpaid',
                        ],
                        'active' => $request->getParam('controller') == 'Expenses' &&
                        $request->getParam('action') == 'reportUnpaid',
                    ],
                    'graph_yearly' => [
                        'visible' => true,
                        'title' => __d('lil_expenses', 'Yearly Graph'),

                        'url' => [
                            'plugin' => 'LilExpenses',
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
                'title' => __d('lil_expenses', 'Import'),
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
                        'title' => __d('lil_expenses', 'Sepa XML'),

                        'url' => [
                            'plugin' => 'LilExpenses',
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
                'title' => __d('lil_expenses', 'Lookups'),
                'url' => false,
                'expandable' => true,
                'active' => in_array($request->getParam('controller'), ['PaymentsAccounts']),
                'expand' => in_array($request->getParam('controller'), ['PaymentsAccounts']),
                'submenu' => [
                    'payments_accounts' => [
                        'visible' => true,
                        'title' => __d('lil_expenses', 'Accounts'),

                        'url' => [
                            'plugin' => 'LilExpenses',
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

        return true;
    }
}
