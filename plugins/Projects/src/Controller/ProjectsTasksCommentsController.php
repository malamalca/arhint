<?php
declare(strict_types=1);

namespace Projects\Controller;

use App\Controller\AppController;

/**
 * ProjectsTasksComments Controller
 *
 * @property \Projects\Model\Table\ProjectsTasksCommentsTable $ProjectsTasksComments
 */
class ProjectsTasksCommentsController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Projects Tasks Comment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        $TasksTable = $this->getTableLocator()->get('Projects.ProjectsTasks');

        if ($id) {
            $taskComment = $this->ProjectsTasksComments->get($id, contain: []);
            $task = $TasksTable->get($taskComment->task_id);
        } else {
            $taskComment = $this->ProjectsTasksComments->newEmptyEntity();
            $task = $TasksTable->get($this->request->getData('task_id'));
        }
        $this->Authorization->authorize($task);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $taskComment = $this->ProjectsTasksComments->patchEntity($taskComment, $this->request->getData());
            if ($this->ProjectsTasksComments->save($taskComment)) {
                $this->Flash->success(__('The projects tasks comment has been saved.'));

                $redirectUrl = $this->request->getData('redirect', [
                    'controller' => 'ProjectsTasks',
                    'action' => 'view',
                    $taskComment->task_id,
                ]);

                return $this->redirect($redirectUrl);
            }
            $this->Flash->error(__('The projects tasks comment could not be saved. Please, try again.'));
        }
        $this->set(compact('taskComment'));
    }

    /**
     * Delete method
     *
     * @param string $id Projects Tasks Comment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(string $id)
    {
        $projectsTasksComment = $this->ProjectsTasksComments->get($id);

        $TasksTable = $this->getTableLocator()->get('Projects.ProjectsTasks');
        $task = $TasksTable->get($projectsTasksComment->task_id);
        $this->Authorization->authorize($task, 'edit');

        if ($this->ProjectsTasksComments->delete($projectsTasksComment)) {
            $this->Flash->success(__('The projects tasks comment has been deleted.'));
        } else {
            $this->Flash->error(__('The projects tasks comment could not be deleted. Please, try again.'));
        }

        $redirectUrl = $this->request->getQuery('redirect', [
            'controller' => 'ProjectsTasks',
            'action' => 'view',
            $projectsTasksComment->task_id,
        ]);

        return $this->redirect($redirectUrl);
    }
}
