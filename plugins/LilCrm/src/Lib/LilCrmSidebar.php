<?php
declare(strict_types=1);

namespace LilCrm\Lib;

class LilCrmSidebar
{
    /**
     * Add admin sidebar elements.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $sidebar Sidebar data.
     * @return bool|void
     */
    public static function setAdminSidebar($event, $sidebar)
    {
        if (!$event->getSubject() instanceof \App\Controller\AppController) {
            return false;
        }

        $request = $event->getSubject()->getRequest();
        $currentUser = $event->getSubject()->getCurrentUser();

        if (empty($currentUser)) {
            return false;
        }

        $crm['title'] = __d('lil_crm', 'Costumers');
        $crm['visible'] = true;
        $crm['active'] = $request->getParam('plugin') == 'LilCrm';
        $crm['url'] = [
            'plugin' => 'LilCrm',
            'controller' => 'Contacts',
            'action' => 'index',
        ];

        $crm['items'] = [
            'contacts' => [
                'visible' => true,
                'title' => __d('lil_crm', 'Contacts'),
                'url' => [
                    'plugin' => 'LilCrm',
                    'controller' => 'Contacts',
                    'action' => 'index',
                ],
                'params' => [],
                'active' => in_array($request->getParam('controller'), ['Contacts']) &&
                            (empty($request->getQuery('kind')) || strtoupper($request->getQuery('kind', '')) == 'T') &&
                            in_array($request->getParam('action'), ['index', 'edit', 'view']),
                'expand' => false,
                'submenu' => [],
            ],
            'companies' => [
                'visible' => true,
                'title' => __d('lil_crm', 'Companies'),
                'url' => [
                    'plugin' => 'LilCrm',
                    'controller' => 'Contacts',
                    'action' => 'index',
                    '?' => ['kind' => 'C'],
                ],
                'params' => [],
                'active' => in_array($request->getParam('controller'), ['Contacts']) &&
                            (strtoupper($request->getQuery('kind', 'T')) == 'C'),
                'expand' => false,
                'submenu' => [],
            ],
            'labels' => [
                'visible' => true,
                'title' => __d('lil_crm', 'Labels'),
                'url' => [
                    'plugin' => 'LilCrm',
                    'controller' => 'Labels',
                    'action' => 'adrema',
                ],
                'params' => [],
                'active' => in_array($request->getParam('controller'), ['Labels']),
                'expand' => false,
                'submenu' => [],
            ],
        ];

        $sidebar->append($crm);

        $event->setResult(['sidebar' => $sidebar]);

        return true;
    }
}
