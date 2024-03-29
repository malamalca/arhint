<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * ProjectsLogs Controller
 *
 * @property \Projects\Model\Table\ProjectsLogsTable $ProjectsLogs
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsLogsController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Projects Log id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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

                    $user = TableRegistry::getTableLocator()->get('Users')->get($projectsLog->user_id);
                    $this->set(compact('projectsLog', 'user'));
                    die((string)$this->render('/Element/projects_log'));
                }

                $this->Flash->success(__d('projects', 'The projects log has been saved.'));

                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect([
                    'controller' => 'Projects',
                    'action' => 'view',
                    $projectsLog->project_id,
                    '?' => ['tab' => 'logs'],
                ]);
            }
            $this->Flash->error(__d('projects', 'The projects log could not be saved. Please, try again.'));
        }

        $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectsLog->project_id);
        $this->set(compact('projectsLog', 'project'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Log id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsLog = $this->ProjectsLogs->get($id);
        $this->Authorization->Authorize($projectsLog);

        if ($this->ProjectsLogs->delete($projectsLog)) {
            $this->Flash->success(__d('projects', 'The projects log has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects log could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Projects', 'action' => 'view', $projectsLog->project_id]);
    }
}
