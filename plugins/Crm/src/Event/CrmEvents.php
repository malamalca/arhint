<?php
declare(strict_types=1);

namespace Crm\Event;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Routing\Router;
use Crm\Lib\CrmSidebar;
use Lil\Lib\LilForm;

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
     * @param \Lil\Lib\LilForm $form Form array
     * @return void
     */
    public function addAutocompleteToEmail(Event $event, LilForm $form): void
    {
        $link = Router::url([
            'plugin' => 'Crm',
            'controller' => 'Contacts',
            'action' => 'autocomplete-email',
        ], true);

        $form->form['post'] .= '<script type="text/javascript">' . PHP_EOL .
            sprintf('$("#to").autocompleteajax({source: "%s"});', $link) . PHP_EOL .
            '</script>' . PHP_EOL;
    }

    /**
     * Add css script to main layout.
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function addScripts(Event $event): void
    {
        /** @var \App\View\AppView $view */
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
    public function modifySidebar(Event $event, ArrayObject $sidebar): void
    {
        if (Configure::read('Crm.showSidebar')) {
            CrmSidebar::setAdminSidebar($event, $sidebar);
        }
    }
}
