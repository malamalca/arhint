<?php
declare(strict_types=1);

namespace Crm\Controller;

use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;
use Cake\Http\Exception\UnauthorizedException;

/**
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @method \App\View\AppView createView(?string $viewClass = null)
 */
class AppController extends BaseController
{
    /**
     * beforeFilterCallback
     *
     * @param \Cake\Event\EventInterface $event Event object
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!$this->getCurrentUser()->hasAccess(\App\AppPluginsEnum::Crm)) {
            throw new UnauthorizedException(__d('crm', 'No Access'));
        }
    }
}
