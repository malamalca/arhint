<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Cache\Cache;
use Cake\Http\Response;

/**
 * ProjectsStatuses Controller
 *
 * @property \Projects\Model\Table\ProjectsStatusesTable $ProjectsStatuses
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsStatusesController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $projectsStatuses = $this->Authorization->applyScope($this->ProjectsStatuses->find())
            ->select()
            ->orderBy('title')
            ->all();

        $this->set(compact('projectsStatuses'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Status id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
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
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
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
