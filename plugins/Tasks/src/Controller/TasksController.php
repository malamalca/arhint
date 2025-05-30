<?php
declare(strict_types=1);

namespace Tasks\Controller;

use Cake\Cache\Cache;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;

/**
 * Tasks Controller
 *
 * @property \Tasks\Model\Table\TasksTable $Tasks
 */
class TasksController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $filter = $this->getRequest()->getQueryParams();
        $filter['user'] = $this->getCurrentUser()->get('id');

        /** @var \Tasks\Model\Table\TasksFoldersTable $TasksFolders */
        $TasksFolders = TableRegistry::getTableLocator()->get('Tasks.TasksFolders');

        $folders = $TasksFolders->listForOwner($this->getCurrentUser()->get('company_id'));

        if (!empty($filter['folder']) && !in_array($filter['folder'], array_keys($folders))) {
            throw new NotFoundException(__d('tasks', 'Folder does not exist.'));
        }

        $params = array_merge_recursive([
            'contain' => ['TasksFolders'],
            'conditions' => [],
            'order' => ['TasksFolders.title ASC', 'Tasks.completed'],
        ], $this->Tasks->filter($filter));

        $tasks = $this->Authorization->applyScope($this->Tasks->find())
            ->select()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->orderBy($params['order'])
            ->all();

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $this->set(compact('tasks', 'filter', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $task = $this->Tasks->get($id);
        } else {
            $task = $this->Tasks->newEmptyEntity();
            $task->owner_id = $this->getCurrentUser()->get('company_id');
            $task->user_id = $this->getCurrentUser()->id;
        }

        $this->Authorization->authorize($task);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $task = $this->Tasks->patchEntity($task, $this->getRequest()->getData());
            if ($this->Tasks->save($task)) {
                $this->Flash->success(__d('tasks', 'The task has been saved.'));

                Cache::delete('Tasks.' . $this->getCurrentUser()->id . '.OpenTasks');

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('tasks', 'The task could not be saved. Please, try again.'));
            }
        }

        /** @var \Tasks\Model\Table\TasksFoldersTable $TasksFolders */
        $TasksFolders = TableRegistry::getTableLocator()->get('Tasks.TasksFolders');

        $folders = $TasksFolders->listForOwner($this->getCurrentUser()->get('company_id'));

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $this->set(compact('task', 'folders', 'users'));

        return null;
    }

    /**
     * Toggle completed
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null
     */
    public function toggle(?string $id = null): ?Response
    {
        $task = $this->Tasks->get($id);
        if (empty($task->completed)) {
            $task->completed = new DateTime();
        } else {
            $task->completed = null;
        }

        $this->Authorization->authorize($task, 'edit');

        if (!$this->Tasks->save($task)) {
            $this->Flash->error(__d('tasks', 'The task could not be saved. Please, try again.'));
        }

        Cache::delete('Tasks.' . $this->getCurrentUser()->id . '.OpenTasks');

        return $this->redirect($this->getRequest()->referer() ?? ['action' => 'index']);
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $task = $this->Tasks->get($id);

        $this->Authorization->authorize($task);

        if ($this->Tasks->delete($task)) {
            $this->Flash->success(__d('tasks', 'The task has been deleted.'));

            Cache::delete('Tasks.' . $this->getCurrentUser()->id . '.OpenTasks');
        } else {
            $this->Flash->error(__d('tasks', 'The task could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
