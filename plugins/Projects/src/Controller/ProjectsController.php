<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use Exception;
use Projects\Lib\ProjectsFuncs;

/**
 * Projects Controller
 *
 * @property \Projects\Model\Table\ProjectsTable $Projects
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsController extends AppController
{
    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        // post from external program like LilScan
        if (in_array($this->getRequest()->getParam('action'), ['linkEmail'])) {
            $this->FormProtection->setConfig('validate', false);
        }
    }

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
                'conditions' => ['Projects.active IN' => [true]],
                'order' => ['Projects.no DESC'],
            ],
            $this->Projects->filter($filter),
        );

        $query = $this->Authorization->applyScope($this->Projects->find())
            ->select($this->Projects)
            ->select(['LastLog.id', 'LastLog.user_id', 'LastLog.project_id', 'LastLog.created', 'LastLog.descript'])
            ->contain(['LastLog' => ['Users']])
            ->where($params['conditions'])
            ->orderBy($params['order']);

        $projects = $this->paginate($query);

        /* Get last log users */
        $userIds = Hash::extract($projects->toArray(), '{n}.last_log.user_id');
        $userIds = array_filter(array_unique($userIds));
        $lastLogUsers = [];
        if (count($userIds) > 0) {
            $lastLogUsers = $this->Projects->Users->find()
                ->select(['id', 'name'])
                ->where(['id IN' => $userIds])
                ->all()
                ->combine('id', fn($entity) => $entity)
                ->toArray();
        }

        /* Get project statuses */
        $projectsStatuses = $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->cache('Projects.ProjectsStatuses.' . $this->getCurrentUser()->get('company_id'))
            ->orderBy(['title'])
            ->toArray();

        $this->set(compact('projects', 'projectsStatuses', 'filter', 'lastLogUsers'));
    }

    /**
     * Map method
     *
     * @return \Cake\Http\Response|void
     */
    public function map()
    {
        $projects = $this->Authorization->applyScope($this->Projects->find(), 'index')
            ->select()
            ->where([
                'active' => true,
            ])
            ->orderBy('title')
            ->all();

        $this->set(compact('projects'));
    }

    /**
     * View method
     *
     * @param string|null $id Project id.
     * @param string $size Image size.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function picture(?string $id = null, string $size = 'normal'): Response
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
    public function view(?string $id = null)
    {
        $project = $this->Projects->get($id, contain: 'Users');

        $this->Authorization->authorize($project);

        /** @var \Projects\Model\Table\ProjectsWorkhoursTable $WorkhoursTable */
        $WorkhoursTable = TableRegistry::getTableLocator()->get('Projects.ProjectsWorkhours');
        $workDuration = $WorkhoursTable->getTotalDuration($project->id);

        $logs = TableRegistry::getTableLocator()->get('Projects.ProjectsLogs')->find()
            ->select()
            ->where(['project_id' => $id])
            ->orderBy('ProjectsLogs.created DESC')
            ->limit(5)
            ->all();

        $milestones = TableRegistry::getTableLocator()->get('Projects.ProjectsMilestones')->find()
            ->select()
            ->where(['project_id' => $id])
            ->orderBy('date_due ASC')
            ->all();

        $userIds = array_unique(Hash::extract($logs->toArray(), '{n}.user_id') +
            Hash::extract($milestones->toArray(), '{n}.user_id'));

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
            ->orderBy(['title'])
            ->toArray();

        $this->viewBuilder()
            ->setOption('rootNode', 'arhint')
            ->setOption('serialize', ['project']);

        $this->set(compact('project', 'logs', 'users', 'projectsStatuses', 'workDuration', 'milestones'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
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

            $icoFile = $this->getRequest()->getData('ico_file');
            if ($icoFile) {
                if ($icoFile->getError() == UPLOAD_ERR_OK) {
                    $icoContents = (string)$icoFile->getStream()->getMetadata('uri');
                    $project->ico = base64_encode((string)file_get_contents($icoContents));
                }
                if ($icoFile->getError() == UPLOAD_ERR_NO_FILE) {
                    unset($project->ico);
                    $project->setDirty('ico', false);
                }
            }

            if ($this->Projects->save($project)) {
                $this->Flash->success(__d('projects', 'The project has been saved.'));
                $redirect = $this->getRequest()->getData('referer', ['action' => 'view', $project->id]);

                return $this->redirect($redirect);
            }
            $this->Flash->error(__d('projects', 'The project could not be saved. Please, try again.'));
        }

        $projectStatuses = $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->orderBy(['title'])
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
    public function user(string $projectId, ?string $userId = null): ?Response
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
                $redirect = $this->getRequest()->getData('redirect', null);
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
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function deleteUser(string $projectId, string $userId): ?Response
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
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
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

    /**
     * Link Email method - links an email to a project
     *
     * @return \Cake\Http\Response
     */
    public function linkEmail(): Response
    {
        $this->getRequest()->allowMethod(['post']);
        $this->Authorization->skipAuthorization();

        // Get JSON data from request body
        $rawInput = (string)$this->getRequest()->getBody();
        $data = json_decode($rawInput, true);

        if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
            // Fall back to getData() for form-encoded data
            $data = $this->getRequest()->getData();
        }

        //$this->log('Parsed data: ' . (string)json_encode($data), 'debug');

        // Validate required fields
        if (empty($data['project_id'])) {
            return $this->response
                ->withType('application/json')
                ->withStatus(400)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Missing required fields: project_id and email',
                ]));
        }

        // Get and authorize project
        try {
            $project = $this->Projects->get($data['project_id']);
            $this->Authorization->authorize($project, 'edit');
        } catch (Exception $e) {
            return $this->response
                ->withType('application/json')
                ->withStatus(404)
                ->withStringBody((string)json_encode([
                    'success' => false,
                    'message' => 'Project not found or access denied',
                ]));
        }

        // Create attachment for the email
        /** @var \App\Model\Table\AttachmentsTable $AttachmentsTable */
        $AttachmentsTable = TableRegistry::getTableLocator()->get('Attachments');

        $attachment = $AttachmentsTable->newEmptyEntity();
        $attachment->model = 'Projects.Projects';
        $attachment->foreign_id = $project->id;
        $attachment->filename = $data['email']['subject'] ?? 'Email';
        $attachment->description = 'Linked email from: ' . ($data['email']['from'] ?? 'Unknown');
        //$attachment->content = (string)json_encode($data['email']);

        /*if ($AttachmentsTable->save($attachment)) {*/
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'message' => 'Email linked successfully',
                    'attachment_id' => 'OK', // $attachment->id,
                ]));
        /*} else {
            return $this->response
                ->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode([
                    'success' => false,
                    'message' => 'Failed to save email attachment',
                ]));
        }*/
    }
}
