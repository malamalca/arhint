<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * ProjectsMilestones Controller
 *
 * @property \Projects\Model\Table\ProjectsMilestonesTable $ProjectsMilestones
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsMilestonesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $projectId = $this->getRequest()->getQuery('project');
        $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectId);
        $this->Authorization->authorize($project, 'view');

        $milestones = $this->Authorization->applyScope($this->ProjectsMilestones->find())
            ->select()
            ->where(['project_id' => $projectId])
            ->orderBy('title')
            ->all();

        $this->set(compact('milestones'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Milestone id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $projectsMilestone = $this->ProjectsMilestones->get($id);
        } else {
            $projectsMilestone = $this->ProjectsMilestones->newEmptyEntity();
            $projectsMilestone->project_id = $this->getRequest()->getQuery('project');
            $projectsMilestone->user_id = $this->getCurrentUser()->get('id');
        }

        $this->Authorization->Authorize($projectsMilestone);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $requestData = $this->getRequest()->getData();

            $projectsMilestone = $this->ProjectsMilestones->patchEntity($projectsMilestone, $requestData);
            if ($this->ProjectsMilestones->save($projectsMilestone)) {
                $this->Flash->success(__d('projects', 'The projects milestone has been saved.'));

                $redirect = $this->getRequest()->getData(
                    'referer',
                    [
                        'controller' => 'Projects',
                        'action' => 'view',
                        $projectsMilestone->project_id,
                        '?' => ['tab' => 'logs'],
                    ],
                );

                return $this->redirect($redirect);
            } else {
                $this->Flash->error(__d('projects', 'The projects milestone could not be saved. Please, try again.'));
            }
        }

        $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectsMilestone->project_id);
        $this->set(compact('projectsMilestone', 'project'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Milestone id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsMilestone = $this->ProjectsMilestones->get($id);
        $this->Authorization->Authorize($projectsMilestone);

        if ($this->ProjectsMilestones->delete($projectsMilestone)) {
            $this->Flash->success(__d('projects', 'The projects milestone has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects milestone could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Projects', 'action' => 'view', $projectsMilestone->project_id]);
    }
}
