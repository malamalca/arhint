<?php
declare(strict_types=1);

namespace App\Event;

use Cake\Event\EventListenerInterface;

class AppEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array
     */
    public function implementedEvents(): array
    {
        return [
            'Lil.Sidebar.beforeRender' => 'modifySidebar',
        ];
    }

    /**
     * Remove welcome from Lil sidebar.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \ArrayObject $sidebar Sidebar.
     * @return \ArrayObject
     */
    public function modifySidebar($event, $sidebar)
    {
        $ret = $sidebar;
        unset($sidebar['welcome']);

        return $ret;
    }
}
