<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class UsersController extends AppController
{
    /**
     * BeforeFilter method.
     *
     * @param \Cake\Event\EventInterface $event Cake Event object.
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        $this->Authentication->allowUnauthenticated(['login', 'reset', 'changePassword', 'avatar']);

        if ($this->getRequest()->getParam('action') == 'login') {
            $this->FormProtection->setConfig('validate', false);
        }
    }

    /**
     * This method will display login form
     *
     * @return \Cake\Http\Response|null
     */
    public function login(): ?Response
    {
        $this->Authorization->skipAuthorization();

        $result = $this->Authentication->getResult();

        // regardless of POST or GET, redirect if user is logged in
        if ($result && $result->isValid()) {
            /** @var \App\Model\Entity\User $user */
            $user = $this->Authentication->getIdentity();

            $redirect = $user->login_redirect ?? $this->Authentication->getLoginRedirect();
            if (empty($redirect)) {
                $redirect = $this->getRequest()->getQuery('redirect', '/');
            }

            return $this->redirect($redirect);
        }

        // display error if user submitted and authentication failed
        if ($this->getRequest()->is(['post']) && (!$result || $result->isValid() !== true)) {
            $this->Flash->error('Invalid username or password');
        }

        return null;
    }

    /**
     * Logout method
     *
     * @return \Cake\Http\Response|null
     */
    public function logout(): ?Response
    {
        $this->Authorization->skipAuthorization();
        $this->Authentication->logout();

        return $this->redirect('/');
    }

    /**
     * Reset method
     *
     * @return \Cake\Http\Response|null
     */
    public function reset(): ?Response
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is('post')) {
            /** @var \App\Model\Entity\User|null $user */
            $user = $this->Users->find()
                ->select()
                ->where(['email' => $this->getRequest()->getData('email')])
                ->first();

            if ($user) {
                $user->reset_key = uniqid();
                if ($this->Users->save($user)) {
                    $this->Users->sendResetEmail($user);
                    $this->Flash->success(__('An email with password reset instructions has been sent.'));
                }
            } else {
                $this->Flash->error(__('No user with specified email has been found.'));
            }
        }

        return null;
    }

    /**
     * Change users password
     *
     * @param string $resetKey Auto generated reset key.
     * @return \Cake\Http\Response|null
     */
    public function changePassword(?string $resetKey = null): ?Response
    {
        $this->Authorization->skipAuthorization();

        if (!$resetKey) {
            throw new NotFoundException(__('Reset key does not exist.'));
        }

        /** @var \App\Model\Entity\User $user */
        $user = $this->Users->find()
            ->select()
            ->where(['reset_key' => $resetKey])
            ->first();

        if ($user == null) {
            throw new NotFoundException(__('User does not exist.'));
        }

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $this->Users->patchEntity($user, $this->getRequest()->getData(), ['validate' => 'resetPassword']);
            $user->passwd = $this->getRequest()->getData('passwd');

            if (!$user->getErrors() && $this->Users->save($user)) {
                $this->Flash->success(__('Password has been changed.'));
                $this->redirect('/');
            } else {
                $this->Flash->error(__('Please verify that the information is correct.'));
            }
        }

        $this->set(compact('user'));

        return null;
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): ?Response
    {
        $q = $this->Authorization->applyScope($this->Users->find())
            ->orderBy('name');

        $this->Users->filter($q, $this->getRequest());
        $users = $q->all();

        $this->set(compact('users'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null): ?Response
    {
        $user = $this->Users->get($id);

        $this->Authorization->authorize($user);

        $this->set('user', $user);

        return null;
    }

    /**
     * Immediatelly login as specified user
     *
     * @param string $id User id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function loginAs(string $id): ?Response
    {
        $this->Authorization->skipAuthorization();
        $user = $this->Users->get($id);
        $this->Authentication->setIdentity($user);

        return $this->redirect('/');
    }

    /**
     * Edit method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $user = $this->Users->get($id);
        } else {
            /** @var \App\Model\Entity\User $user  */
            $user = $this->Users->newEmptyEntity();
            $user->company_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($user);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->getRequest()->getData());

            if (empty($this->getRequest()->getData('passwd'))) {
                unset($user->passwd);
            } else {
                $user->passwd = $this->getRequest()->getData('passwd');
            }

            if ($this->Users->save($user)) {
                $this->Flash->success(__('The user has been saved.'));

                return $this->redirect(['action' => 'view', $user->id]);
            }
            $this->Flash->error(__('The user could not be saved. Please, try again.'));
        }

        $this->set(compact('user'));

        return null;
    }

    /**
     * Properties method is for users editing their own data.
     *
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function properties(): ?Response
    {
        $user = $this->Users->get($this->getCurrentUser()->get('id'));

        $this->Authorization->authorize($user);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $user = $this->Users->patchEntity($user, $this->getRequest()->getData(), ['validate' => 'properties']);

            $avatarFile = $this->getRequest()->getData('avatar_file');
            if ($avatarFile) {
                if ($avatarFile->getError() == UPLOAD_ERR_OK) {
                    $avatarData = (string)file_get_contents($avatarFile->getStream()->getMetadata('uri'));
                    $user->avatar = base64_encode($avatarData);
                }
                if ($avatarFile->getError() == UPLOAD_ERR_NO_FILE) {
                    unset($user->avatar);
                    $user->setDirty('avatar', false);
                }
            }

            if (empty($this->getRequest()->getData('passwd'))) {
                unset($user->passwd);
            } else {
                $user->passwd = $this->getRequest()->getData('passwd');
            }

            if ($this->Users->save($user)) {
                $this->Flash->success(__('Properties have been saved.'));

                return $this->redirect(['action' => 'view', $user->id]);
            }
            $this->Flash->error(__('Properties could not be saved. Please, try again.'));
        }

        $this->set(compact('user'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['get']);
        $user = $this->Users->get($id);
        $this->Authorization->authorize($user);

        if ($this->Users->delete($user)) {
            $this->Flash->success(__('The user has been deleted.'));
        } else {
            $this->Flash->error(__('The user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Avatar method
     *
     * @param string|null $id User id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function avatar(?string $id = null): Response
    {
        if (!empty($id)) {
            $user = $this->Users->get($id);
            $this->Authorization->authorize($user, 'view');

            $imageData = $user->getAvatarImage();
        } else {
            $this->Authorization->skipAuthorization();
        }

        if (empty($imageData)) {
            $imageData = file_get_contents(constant('WWW_ROOT') . 'img' . DS . 'avatar.png');
        }

        $response = $this->response
            ->withStringBody((string)$imageData)
            ->withType('png')
            ->withCache('-1 day', '+30 days');

        return $response;
    }
}
