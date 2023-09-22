<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Entity\User;
use Cake\Controller\Controller;
use Cake\Event\EventInterface;
use Exception;

/**
 * Application Controller
 *
 * Add your application-wide methods in the class below, your controllers
 * will inherit them.
 *
 * @property \Authentication\Controller\Component\AuthenticationComponent $Authentication
 * @property \Authorization\Controller\Component\AuthorizationComponent $Authorization
 * @link https://book.cakephp.org/4/en/controllers.html#the-app-controller
 */
class AppController extends Controller
{
    /**
     * @var \App\Model\Entity\User|null $currentUser
     */
    protected ?User $currentUser = null;

    /**
     * Initialization hook method.
     *
     * Use this method to add common initialization code like loading components.
     *
     * e.g. `$this->loadComponent('Security');`
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');

        /*
         * Enable the following component for recommended CakePHP form protection settings.
         * see https://book.cakephp.org/4/en/controllers/components/form-protection.html
         */
        $this->loadComponent('FormProtection');

        $this->response->setTypeMap('aht', ['text/html']);
    }

    /**
     * Returns is user is logged on.
     *
     * @return bool
     */
    public function hasCurrentUser(): bool
    {
        return !empty($this->currentUser);
    }

    /**
     * Returns current user.
     *
     * @return \App\Model\Entity\User
     */
    public function getCurrentUser(): User
    {
        if (!$this->currentUser) {
            throw new Exception('User does not exist.');
        }

        return $this->currentUser;
    }

    /**
     * beforeFilterCallback
     *
     * @param \Cake\Event\EventInterface $event Event object
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->Authentication->getIdentity();
        if ($user) {
            $this->currentUser = $user->getOriginalData();
        }
    }
}
