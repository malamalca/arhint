<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use Cake\ORM\TableRegistry;

/**
 * ProjectsLogs Controller
 *
 * @property \LilProjects\Model\Table\ProjectsLogsTable $ProjectsLogs
 * @method \LilProjects\Model\Entity\ProjectsLog[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsLogsController extends AppController
{
    /**
     * Add method
     *
     * @return void
     */
    public function add()
    {
        $this->setAction('edit');
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Log id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $projectsLog = $this->ProjectsLogs->get($id);
        } else {
            $projectsLog = $this->ProjectsLogs->newEmptyEntity();
            $projectsLog->project_id = $this->getRequest()->getQuery('project');
            $projectsLog->user_id = $this->getCurrentUser()->get('id');
        }

        $this->Authorization->Authorize($projectsLog);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $projectsLog = $this->ProjectsLogs->patchEntity($projectsLog, $this->request->getData());
            if ($this->ProjectsLogs->save($projectsLog)) {
                if ($this->getRequest()->is('ajax')) {
                    header('Content-Type: text/hml');

                    $user = (TableRegistry::get('Users'))->get($projectsLog->user_id);
                    $this->set(compact('projectsLog', 'user'));
                    die($this->render('/element/projects_log'));
                }

                $this->Flash->success(__d('lil_projects', 'The projects log has been saved.'));

                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect(['controller' => 'Projects', 'action' => 'view', $projectsLog->project_id]);
            }
            $this->Flash->error(__d('lil_projects', 'The projects log could not be saved. Please, try again.'));
        }

        $project = TableRegistry::getTableLocator()->get('LilProjects.Projects')->get($projectsLog->project_id);
        $this->set(compact('projectsLog', 'project'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Log id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsLog = $this->ProjectsLogs->get($id);
        $this->Authorization->Authorize($projectsLog);

        if ($this->ProjectsLogs->delete($projectsLog)) {
            $this->Flash->success(__d('lil_projects', 'The projects log has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_projects', 'The projects log could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Projects', 'action' => 'view', $projectsLog->project_id]);
    }
}
