<?php
declare(strict_types=1);

namespace LilTasks\Controller;

use Cake\Http\Exception\NotFoundException;
use Cake\I18n\Time;

/**
 * Tasks Controller
 *
 * @property \LilTasks\Model\Table\TasksTable $Tasks
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

        $folders = $this->Tasks->TasksFolders->listForOwner($this->getCurrentUser()->get('company_id'));

        if (!empty($filter['folder']) && !in_array($filter['folder'], array_keys($folders))) {
            throw new NotFoundException(__d('lil_tasks', 'Folder does not exist.'));
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
            ->order($params['order'])
            ->all();

        $this->set(compact('tasks', 'filter'));
    }

    /**
     * Add method
     *
     * @return mixed Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $ret = $this->setAction('edit');

        return $ret;
    }

    /**
     * Edit method
     *
     * @param string|null $id Task id.
     * @return mixed
     */
    public function edit($id = null)
    {
        if ($id) {
            $task = $this->Tasks->get($id);
        } else {
            $task = $this->Tasks->newEmptyEntity();
            $task->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($task);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $task = $this->Tasks->patchEntity($task, $this->getRequest()->getData());
            if ($this->Tasks->save($task)) {
                $this->Flash->success(__d('lil_tasks', 'The task has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('lil_tasks', 'The task could not be saved. Please, try again.'));
            }
        }

        $folders = $this->Tasks->TasksFolders->listForOwner($this->getCurrentUser()->get('company_id'));

        $this->set(compact('task', 'folders'));
    }

    /**
     * Toggle completed
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|null Redirects to index.
     */
    public function toggle($id = null)
    {
        $task = $this->Tasks->get($id);
        if (empty($task->completed)) {
            $task->completed = new Time();
        } else {
            $task->completed = null;
        }

        $this->Authorization->authorize($task, 'edit');

        if (!$this->Tasks->save($task)) {
            $this->Flash->error(__d('lil_tasks', 'The task could not be saved. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->referer());
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response Redirects to index.
     */
    public function delete($id = null)
    {
        $task = $this->Tasks->get($id);

        $this->Authorization->authorize($task);

        if ($this->Tasks->delete($task)) {
            $this->Flash->success(__d('lil_tasks', 'The task has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_tasks', 'The task could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->referer());
    }
}
