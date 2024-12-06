<?php
declare(strict_types=1);

namespace Projects\Controller;

use App\AppPluginsEnum;
use App\Controller\AppController as BaseController;
use Cake\Event\EventInterface;
use Cake\Http\Exception\UnauthorizedException;

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

        if (!$this->getCurrentUser()->hasAccess(AppPluginsEnum::Projects)) {
            throw new UnauthorizedException(__d('projects', 'No Access'));
        }
    }
}
