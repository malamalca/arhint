<?php
declare(strict_types=1);

namespace Projects\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

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
        if ($id) {
            $taskComment = $this->ProjectsTasksComments->get($id, contain: []);
        } else {
            $taskComment = $this->ProjectsTasksComments->newEmptyEntity();
        }
        $this->Authorization->authorize($taskComment);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $taskComment = $this->ProjectsTasksComments->patchEntity($taskComment, $this->request->getData());
            if ($this->ProjectsTasksComments->save($taskComment)) {
                $this->Flash->success(__d('projects', 'The projects tasks comment has been saved.'));

                $redirectUrl = $this->request->getData('referer', [
                    'controller' => 'ProjectsTasks',
                    'action' => 'view',
                    $taskComment->task_id,
                ]);

                return $this->redirect($redirectUrl);
            }
            $this->Flash->error(__d('projects', 'The projects tasks comment could not be saved. Please, try again.'));
        }

        /** @var \App\Model\Table\UsersTable  $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $this->set(compact('taskComment', 'users'));
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
        $this->Authorization->authorize($projectsTasksComment);

        if ($this->ProjectsTasksComments->delete($projectsTasksComment)) {
            $this->Flash->success(__d('projects', 'The projects tasks comment has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects tasks comment could not be deleted. Please, try again.'));
        }

        $redirectUrl = $this->request->getQuery('redirect', [
            'controller' => 'ProjectsTasks',
            'action' => 'view',
            $projectsTasksComment->task_id,
        ]);

        return $this->redirect($redirectUrl);
    }
}
