<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;
use LilProjects\Lib\LilProjectsFuncs;

/**
 * Projects Controller
 *
 * @property \LilProjects\Model\Table\ProjectsTable $Projects
 * @method \LilProjects\Model\Entity\Project[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();

        $filter['order'] = 'Projects.no';

        $params = array_merge_recursive(
            [
                'conditions' => [
                    'Projects.active IN' => [true],
                ],
                'order' => ['Projects.no DESC'],
            ],
            $this->Projects->filter($filter)
        );

        $query = $this->Authorization->applyScope($this->Projects->find())
            ->select()
            ->contain(['LastLog' => ['Users']])
            ->where($params['conditions'])
            ->order($params['order']);

        $projects = $this->paginate($query);

        $projectsStatuses = $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->cache('Projects.ProjectsStatuses.' . $this->getCurrentUser()->get('company_id'))
            ->order(['title'])
            ->toArray();

        $this->set(compact('projects', 'projectsStatuses'));
    }

    /**
     * Map method
     *
     * @return \Cake\Http\Response|void
     */
    public function map()
    {
        $projects = $this->Authorization->applyScope($this->Projects->find())
            ->select()
            ->where([
                'active' => true,
            ])
            ->order('title')
            ->all();

        $this->set(compact('projects'));
    }

    /**
     * View method
     *
     * @param string|null $id Project id.
     * @param string $size Image size.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function picture($id = null, $size = 'normal')
    {
        $project = $this->Projects->get($id);

        $this->Authorization->authorize($project, 'view');

        $imageData = null;
        switch ($size) {
            case 'thumb':
                //$imageData = Cache::remember($project->id . '-thumb', function () use ($project) {
                //    return LilProjectsFuncs::thumb($project);
                //});
                $imageData = LilProjectsFuncs::thumb($project);

                break;
            default:
                if (!empty($project->ico)) {
                    $imageData = base64_decode($project->ico);
                }
        }

        $response = $this->response;
        $response = $response->withStringBody($imageData);
        $response = $response->withType('png');

        return $response;
    }

    /**
     * View method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $project = $this->Projects->get($id);

        $this->Authorization->authorize($project);

        /** @var \LilProjects\Model\Table\ProjectsWorkhoursTable $WorkhoursTable */
        $WorkhoursTable = TableRegistry::getTableLocator()->get('LilProjects.ProjectsWorkhours');
        $workDuration = $WorkhoursTable->getTotalDuration($project->id);

        $logs = TableRegistry::getTableLocator()->get('LilProjects.ProjectsLogs')->find()
            ->select()
            ->where(['project_id' => $id])
            ->order('ProjectsLogs.created DESC')
            ->limit(5)
            ->all();

        $userIds = [];
        foreach ($logs as $log) {
            $userIds[] = $log->user_id;
        }

        $users = [];
        if (!empty($userIds)) {
            $users = TableRegistry::getTableLocator()->get('Users')->find()
                ->select(['id', 'name'])
                ->where(['id IN' => $userIds])
                ->combine('id', function ($entity) {
                    return $entity;
                })
                ->toArray();
        }

        if ($this->getRequest()->is('ajax')) {
            $this->viewBuilder()->setTemplate('map_popup');
        }

        $projectsStatuses = $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->order(['title'])
            ->toArray();

        $this->set(compact('project', 'logs', 'users', 'projectsStatuses', 'workDuration'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->setAction('edit');
    }

    /**
     * Edit method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $project = $this->Projects->get($id);
        } else {
            $project = $this->Projects->newEmptyEntity();
            $project->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($project);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $project = $this->Projects->patchEntity($project, $this->getRequest()->getData());

            $icoFile = $this->getRequest()->getData('ico');
            if (is_array($icoFile)) {
                if (isset($icoFile['error'])) {
                    if ($icoFile['error'] == UPLOAD_ERR_OK) {
                        $project->ico = base64_encode(file_get_contents($icoFile['tmp_name']));
                    }
                    if ($icoFile['error'] == UPLOAD_ERR_NO_FILE) {
                        unset($project->ico);
                        $project->setDirty('ico', false);
                    }
                }
            }

            if ($this->Projects->save($project)) {
                Cache::delete('LilProjects.projectsList.' . $project->owner_id);

                $this->Flash->success(__d('lil_projects', 'The project has been saved.'));
                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('lil_projects', 'The project could not be saved. Please, try again.'));
        }

        $projectStatuses = $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->order(['title'])
            ->toArray();

        $this->set(compact('project', 'projectStatuses'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete', 'get']);
        $project = $this->Projects->get($id);
        $this->Authorization->authorize($project);
        if ($this->Projects->delete($project)) {
            Cache::delete('LilProjects.projectsList.' . $project->owner_id);
            $this->Flash->success(__d('lil_projects', 'The project has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_projects', 'The project could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
