<?php
declare(strict_types=1);

namespace Crm\Lib;

use App\AppPluginsEnum;
use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;

class CrmSidebar
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

        if (!$controller->getCurrentUser()->hasAccess(AppPluginsEnum::Crm)) {
            return;
        }

        $request = $controller->getRequest();

        $crm['title'] = __d('crm', 'Costumers');
        $crm['visible'] = true;
        $crm['active'] = $request->getParam('plugin') == 'Crm';
        $crm['url'] = [
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'index',
        ];

        $contactKind = $request->getQuery('kind');
        if (!$contactKind) {
            $contact = $event->getSubject()->viewBuilder()->getVar('contact');
            $contactKind = $contact->kind ?? '';
        }
        if (!$contactKind && in_array($request->getParam('action'), ['index', 'edit', 'view'])) {
            $contactKind = 'T';
        }

        $crm['items'] = [
            'contacts' => [
                'visible' => true,
                'title' => __d('crm', 'Contacts'),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Contacts',
                    'action' => 'index',
                ],
                'params' => [],
                'active' => in_array($request->getParam('controller'), ['Contacts']) &&
                            (strtoupper($contactKind) == 'T'),
                'expand' => false,
                'submenu' => [],
            ],
            'companies' => [
                'visible' => true,
                'title' => __d('crm', 'Companies'),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Contacts',
                    'action' => 'index',
                    '?' => ['kind' => 'C'],
                ],
                'params' => [],
                'active' => in_array($request->getParam('controller'), ['Contacts']) &&
                            (strtoupper($contactKind) == 'C'),
                'expand' => false,
                'submenu' => [],
            ],
            'adremas' => [
                'visible' => true,
                'title' => __d('crm', 'Labels'),
                'url' => [
                    'plugin' => 'Crm',
                    'controller' => 'Adremas',
                    'action' => 'index',
                ],
                'params' => [],
                'active' => in_array($request->getParam('controller'), ['Labels', 'Adremas']),
                'expand' => false,
                'submenu' => [],
            ],
        ];

        $sidebar->append($crm);

        $event->setResult(['sidebar' => $sidebar]);
    }
}
