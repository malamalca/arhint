<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use Cake\Http\Response;

/**
 * ProjectsTasks Controller
 *
 * @property \Projects\Model\Table\ProjectsTasksTable $ProjectsTasks
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsTasksController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();

        $params = $this->ProjectsTasks->filter($filter);
        $query = $this->Authorization->applyScope($this->ProjectsTasks->find(), 'index')
            ->where($params['conditions']);

        $query->contain(['Projects', 'Users']);
        $projectsTasks = $this->paginate($query, ['order' => ['started' => 'DESC']]);

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $projects = $this->Authorization->applyScope($this->ProjectsTasks->Projects->find(), 'index')
            ->where(['active' => true])
            ->orderBy(['no DESC', 'title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        $this->set(compact('projectsTasks', 'filter', 'users', 'projects'));
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
            $projectsTask = $this->ProjectsTasks->get($id);
        } else {
            $projectsTask = $this->ProjectsTasks->newEmptyEntity();
            $projectsTask->owner_id = $this->getCurrentUser()->get('company_id');
            $projectsTask->user_id = $this->getCurrentUser()->get('id');
            $projectsTask->project_id = $this->request->getQuery('project');
        }

        $this->Authorization->authorize($projectsTask);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $projectsTask = $this->ProjectsTasks->patchEntity($projectsTask, $this->request->getData());
            if ($this->ProjectsTasks->save($projectsTask)) {
                $this->Flash->success(__d('projects', 'The projects task has been saved.'));

                return $this->redirect($this->getRequest()->getData('redirect', [
                    'controller' => 'Projects',
                    'action' => 'view',
                    $projectsTask->project_id,
                ]));
            }
            $this->Flash->error(__d('projects', 'The projects task could not be saved. Please, try again.'));
        }

        $this->set(compact('projectsTask'));
        $this->set('project', $this->ProjectsTasks->Projects->get($projectsTask->project_id));
        $this->set('milestones', $this->ProjectsTasks->Milestones->find('list')
            ->where(['project_id' => $projectsTask->project_id])
            ->orderBy('title')
            ->toArray()
        );

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
        $projectsTask = $this->ProjectsTasks->get($id);
        $this->Authorization->authorize($projectsTask);
        if ($this->ProjectsTasks->delete($projectsTask)) {
            $this->Flash->success(__d('projects', 'The projects task has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects task could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
