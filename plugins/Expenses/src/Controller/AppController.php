<?php
declare(strict_types=1);

namespace Expenses\Controller;

use App\AppPluginsEnum;
use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;
use Cake\Http\Exception\UnauthorizedException;

/**
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
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

        if ($this->hasCurrentUser() && !$this->getCurrentUser()->hasAccess(AppPluginsEnum::Expenses)) {
            throw new UnauthorizedException(__d('expenses', 'No Access'));
        }
    }
}
