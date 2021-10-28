<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Event\EventInterface;

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
    protected $currentUser = null;

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

        $this->loadComponent('RequestHandler', [
            'viewClassMap' => [
                'aht' => 'App.AhtView',
            ],
        ]);
        $this->loadComponent('Flash');

        /*
         * Enable the following component for recommended CakePHP security settings.
         * see https://book.cakephp.org/4/en/controllers/components/security.html
         */
        $this->loadComponent('Security');

        $this->loadComponent('Authentication.Authentication');
        $this->loadComponent('Authorization.Authorization');

        //Type::build('float')->useLocaleParser();
        //Type::build('decimal')->useLocaleParser();

        $this->response->setTypeMap('aht', ['text/html']);
    }

    /**
     * Returns current user.
     *
     * @return \App\Model\Entity\User|null
     */
    public function getCurrentUser()
    {
        return $this->currentUser;
    }

    /**
     * beforeFilterCallback
     *
     * @param \Cake\Event\EventInterface $event Event object
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event)
    {
        /** @var \App\Model\Entity\User $user */
        $user = $this->Authentication->getIdentity();
        $this->currentUser = $user;

        return null;
    }
}
