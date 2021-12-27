<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Cache\Cache;

/**
 * ProjectsStatuses Controller
 *
 * @property \Projects\Model\Table\ProjectsStatusesTable $ProjectsStatuses
 * @method \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet paginate($object = null, array $settings = [])
 */
class ProjectsStatusesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $projectsStatuses = $this->Authorization->applyScope($this->ProjectsStatuses->find())
            ->select()
            ->order('title')
            ->all();

        $this->set(compact('projectsStatuses'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Status id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if (!empty($id)) {
            $projectsStatus = $this->ProjectsStatuses->get($id);
        } else {
            $projectsStatus = $this->ProjectsStatuses->newEmptyEntity();
            $projectsStatus->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($projectsStatus);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $projectsStatus = $this->ProjectsStatuses->patchEntity($projectsStatus, $this->request->getData());
            if ($this->ProjectsStatuses->save($projectsStatus)) {
                Cache::delete('Projects.ProjectsStatuses.' . $projectsStatus->owner_id);

                $this->Flash->success(__d('projects', 'The projects status has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('projects', 'The projects status could not be saved. Please, try again.'));
        }
        $this->set(compact('projectsStatus'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Status id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsStatus = $this->ProjectsStatuses->get($id);
        $this->Authorization->authorize($projectsStatus);
        if ($this->ProjectsStatuses->delete($projectsStatus)) {
            Cache::delete('Projects.ProjectsStatuses.' . $projectsStatus->owner_id);
            $this->Flash->success(__d('projects', 'The projects status has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects status could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
