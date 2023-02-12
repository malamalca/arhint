<?php
declare(strict_types=1);

namespace Crm\Event;

use Cake\Core\Configure;
use Cake\Event\EventListenerInterface;
use Cake\Routing\Router;
use Crm\Lib\CrmSidebar;

class CrmEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'View.beforeRender' => 'addScripts',
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
            'Lil.Form.Documents.Documents.email' => 'addAutocompleteToEmail',
        ];
    }

    /**
     * Add autocomplete func to email field
     *
     * @param \Cake\Event\Event $event Event.
     * @param \ArrayObject $form Form
     * @return void
     */
    public function addAutocompleteToEmail($event, $form)
    {
        $view = $event->getSubject();

        $link = Router::url([
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'autocomplete-email',
        ], true);

        if (isset($form['form']['post'])) {
            $form['form']['post'] .= '<script type="text/javascript">' . PHP_EOL .
                sprintf('$("#to").autocompleteajax({source: "%s"});', $link) . PHP_EOL .
                '</script>' . PHP_EOL;
        }
    }

    /**
     * Add css script to main layout.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function addScripts($event)
    {
        $view = $event->getSubject();
        $view->append('script');
        echo $view->Html->css('Crm.crm');
        $view->end();

        if ($view->getRequest()->getParam('plugin') == 'Crm') {
            $view->set('admin_title', __d('crm', 'Costumers'));
        }
    }

    /**
     * Modify Lil sidebar.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \ArrayObject $sidebar Sidebar.
     * @return void
     */
    public function modifySidebar($event, $sidebar)
    {
        $ret = $sidebar;
        if (Configure::read('Crm.showSidebar')) {
            CrmSidebar::setAdminSidebar($event, $sidebar);
        }
    }
}
