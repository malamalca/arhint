<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\ORM\TableRegistry;
use Projects\Lib\ProjectsFuncs;

/**
 * Projects Controller
 *
 * @property \Projects\Model\Table\ProjectsTable $Projects
 * @method \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet paginate($object = null, array $settings = [])
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
                //    return ProjectsFuncs::thumb($project);
                //});
                $imageData = ProjectsFuncs::thumb($project);

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
        $project = $this->Projects->get($id, ['contain' => 'Users']);

        $this->Authorization->authorize($project);

        /** @var \Projects\Model\Table\ProjectsWorkhoursTable $WorkhoursTable */
        $WorkhoursTable = TableRegistry::getTableLocator()->get('Projects.ProjectsWorkhours');
        $workDuration = $WorkhoursTable->getTotalDuration($project->id);

        $logs = TableRegistry::getTableLocator()->get('Projects.ProjectsLogs')->find()
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
                ->all()
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
                $this->Flash->success(__d('projects', 'The project has been saved.'));
                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('projects', 'The project could not be saved. Please, try again.'));
        }

        $projectStatuses = $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->order(['title'])
            ->toArray();

        $this->set(compact('project', 'projectStatuses'));

        return null;
    }

    /**
     * User method
     *
     * @param string $projectId Project id.
     * @param string|null $userId User id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function user($projectId, $userId = null)
    {
        $project = $this->Projects->get($projectId);
        $this->Authorization->authorize($project);

        /** @var \Projects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
        $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');

        $projectsUser = null;
        if ($userId) {
            $projectsUser = $ProjectsUsersTable->find()
                ->select()
                ->where(['project_id' => $projectId, 'user_id' => $userId])
                ->first();
        }

        if (!$projectsUser) {
            $projectsUser = $ProjectsUsersTable->newEmptyEntity();
            $projectsUser->project_id = $projectId;
        }

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $projectsUser = $ProjectsUsersTable->patchEntity($projectsUser, $this->getRequest()->getData());

            if ($ProjectsUsersTable->save($projectsUser)) {
                $this->Flash->success(__d('projects', 'The project user has been saved.'));
                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
            }
            $this->Flash->error(__d('projects', 'The project user could not be saved. Please, try again.'));
        }

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));
        foreach ($users as $i => $user) {
            if ($user->hasRole('admin')) {
                unset($users[$i]);
            }
        }
        if (count($users) == 0) {
            $this->Flash->error(__d('projects', 'No users found.'));

            return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
        }

        $this->set(compact('projectsUser', 'users'));

        return null;
    }

    /**
     * Delete user method
     *
     * @param string $projectId Project id.
     * @param string $userId User id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function deleteUser($projectId, $userId)
    {
        $this->getRequest()->allowMethod(['post', 'delete', 'get']);
        $project = $this->Projects->get($projectId);
        $this->Authorization->authorize($project, 'delete');

        $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');
        $projectsUser = $ProjectsUsersTable->find()
            ->select()
            ->where(['project_id' => $projectId, 'user_id' => $userId])
            ->first();

        if (!$projectsUser) {
            $this->Flash->error(__d('projects', 'No link found.'));

            return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
        }

        if ($ProjectsUsersTable->delete($projectsUser)) {
            $this->Flash->success(__d('projects', 'The project user has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The project user could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
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
            $this->Flash->success(__d('projects', 'The project has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The project could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
