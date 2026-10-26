<?php
declare(strict_types=1);

namespace Projects\Controller;

use App\AppPluginsEnum;
use App\Model\Entity\User;
use Cake\Core\Plugin;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Utility\Hash;
use InvalidArgumentException;
use Projects\Lib\AuthorityWorkflow;
use Projects\Lib\KnowledgeBase\KbDiff;
use Projects\Lib\KnowledgeBase\KbService;
use Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException;
use Projects\Lib\ProjectsFuncs;
use Projects\Model\Entity\Project;

/**
 * Projects Controller
 *
 * @property \Projects\Model\Table\ProjectsTable $Projects
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
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
                'conditions' => ['Projects.active IN' => [true]],
                'order' => ['Projects.no DESC'],
            ],
            $this->Projects->filter($filter),
        );

        $query = $this->Authorization->applyScope($this->Projects->find())
            ->select($this->Projects)
            ->select(['LastLog.id', 'LastLog.user_id', 'LastLog.foreign_id', 'LastLog.created', 'LastLog.descript'])
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

        $logs = TableRegistry::getTableLocator()->get('App.Logs')->find()
            ->select()
            ->where([
                'Logs.model' => 'Project',
                'Logs.foreign_id' => $id,
                'Logs.action' => 'Comment',
            ])
            ->orderBy('Logs.created DESC')
            ->limit(5)
            ->all();

        // Open milestones are shown by default; pass ms=all to show every one.
        $milestoneStatus = $this->getRequest()->getQuery('ms', 'open');

        $milestonesQuery = TableRegistry::getTableLocator()->get('Projects.ProjectsMilestones')->find()
            ->select()
            ->where(['project_id' => $id]);

        if ($milestoneStatus === 'open') {
            $milestonesQuery->where(['ProjectsMilestones.date_complete IS' => null]);
        } elseif ($milestoneStatus === 'closed') {
            $milestonesQuery->where(['ProjectsMilestones.date_complete IS NOT' => null]);
        }

        $milestones = $milestonesQuery->orderBy(['date_complete' => 'ASC', 'title' => 'ASC'])->all();

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

        if ($this->getRequest()->getQuery('tab', 'authorities') === 'authorities') {
            $workflow = new AuthorityWorkflow();
            $authorityCases = $workflow->cases($project->id);
            $authorityReviews = $this->fetchTable('Projects.ProjectsAuthorityReviews')->find()
                ->where(['project_id' => $project->id])->contain(['Users'])->orderBy(['sequence'])->all()->toList();
            $authoritySummary = $workflow->summary($project, $authorityCases, $authorityReviews);
            $this->set(compact('authorityCases', 'authorityReviews', 'authoritySummary'));
        }
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
            // The description is the knowledge base; it is saved by KbService, not as a plain field.
            $data = (array)$this->getRequest()->getData();
            $markdown = array_key_exists('descript', $data) ? (string)$data['descript'] : null;
            $expectedVersion = ($data['kb_version'] ?? '') === '' ? null : (int)$data['kb_version'];
            unset($data['descript'], $data['kb_version']);

            $isNew = $project->isNew();
            $project = $this->Projects->patchEntity($project, $data);

            if (!$isNew && $markdown !== null && !$this->saveKnowledgeBase($project, $markdown, $expectedVersion)) {
                $this->set('projectStatuses', $this->projectStatuses());
                $this->set(compact('project'));

                return null;
            }

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
                if ($isNew && $markdown !== null) {
                    $this->saveKnowledgeBase($project, $markdown, null);
                }
                $this->Flash->success(__d('projects', 'The project has been saved.'));
                $redirect = $this->getRequest()->getData('referer', ['action' => 'view', $project->id]);

                return $this->redirect($redirect);
            }
            $this->Flash->error(__d('projects', 'The project could not be saved. Please, try again.'));
        }

        $this->set('projectStatuses', $this->projectStatuses());
        $this->set(compact('project'));

        return null;
    }

    /**
     * Save the description field as the knowledge base of the project.
     *
     * @param \Projects\Model\Entity\Project $project Saved project; on failure it shows the submitted text.
     * @param string $markdown Submitted text.
     * @param int|null $expectedVersion Version of the knowledge base the form was rendered from.
     * @return bool False when the text was refused and the form has to be shown again.
     */
    private function saveKnowledgeBase(Project $project, string $markdown, ?int $expectedVersion): bool
    {
        if (trim($markdown) === '') {
            if ((int)$project->kb_format === 1) {
                $this->Flash->error(__d('projects', 'The knowledge base cannot be emptied, the text was not changed.'));
            }

            return true;
        }

        try {
            (new KbService())->saveManual(
                (string)$project->id,
                $markdown,
                $expectedVersion,
                (string)$this->getCurrentUser()->get('id'),
            );
        } catch (StaleKnowledgeBaseException) {
            $this->Flash->error(__d(
                'projects',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                'The knowledge base was changed in the meantime. Your text is kept in the form; check the history before saving it again.',
            ));
            // Saving again replaces what was written meanwhile, so the form carries the current version.
            // The form helper takes values from the request before the entity.
            $this->setRequest($this->getRequest()->withData(
                'kb_version',
                (int)$this->Projects->get($project->id)->kb_version,
            ));

            return false;
        }

        // The form can be shown again (e.g. a field of the project is invalid), and then it has to match the stored text.
        $stored = $this->Projects->get($project->id);
        foreach (['descript', 'kb_version', 'kb_format'] as $field) {
            $project->set($field, $stored->get($field), ['guard' => false]);
            $project->setDirty($field, false);
        }
        $this->setRequest($this->getRequest()
            ->withData('kb_version', (int)$stored->kb_version)
            ->withData('descript', $stored->descript));

        return true;
    }

    /**
     * @return array<string, string>
     */
    private function projectStatuses(): array
    {
        return $this->Authorization->applyScope($this->Projects->ProjectsStatuses->find('list'), 'index')
            ->orderBy(['title'])
            ->toArray();
    }

    /**
     * History of the knowledge base.
     *
     * With `version` in the query the changes made by that version are shown.
     *
     * @param string|null $id Project id.
     * @return \Cake\Http\Response|null|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function history(?string $id = null)
    {
        $project = $this->Projects->get($id);
        $this->Authorization->authorize($project, 'knowledgeBase');

        $revisionsTable = $this->fetchTable('Projects.ProjectsKbRevisions');
        $revisions = $revisionsTable->find()
            ->select(['id', 'project_id', 'user_id', 'version', 'source', 'summary', 'created'])
            ->contain(['Users' => fn($query) => $query->select(['id', 'name'])])
            ->where(['ProjectsKbRevisions.project_id' => $project->id])
            ->orderBy(['ProjectsKbRevisions.version' => 'DESC'])
            ->limit(200)
            ->all();

        $diff = null;
        $version = $this->getRequest()->getQuery('version');
        if (is_numeric($version)) {
            $pair = $revisionsTable->find()
                ->select(['version', 'descript'])
                ->where([
                    'ProjectsKbRevisions.project_id' => $project->id,
                    'ProjectsKbRevisions.version IN' => [(int)$version - 1, (int)$version],
                ])
                ->all()
                ->combine('version', 'descript')
                ->toArray();
            if (isset($pair[(int)$version])) {
                $diff = (new KbDiff())->diff($pair[(int)$version - 1] ?? null, (string)$pair[(int)$version]);
            }
        }

        $this->set(compact('project', 'revisions', 'diff', 'version'));
    }

    /**
     * Return to the state of an earlier revision.
     *
     * @param string|null $id Revision id.
     * @return \Cake\Http\Response
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function revert(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $revision = $this->fetchTable('Projects.ProjectsKbRevisions')->get($id);
        $project = $this->Projects->get($revision->project_id);
        $this->Authorization->authorize($project, 'knowledgeBase');

        try {
            $userId = (string)$this->getCurrentUser()->get('id');
            (new KbService())->revert((string)$project->id, (string)$revision->id, $userId);
            $this->Flash->success(
                __d('projects', 'The knowledge base was returned to version {0}.', $revision->version),
            );
        } catch (InvalidArgumentException) {
            $this->Flash->error(__d('projects', 'The knowledge base cannot be returned to this version.'));
        }

        return $this->redirect(['action' => 'history', $project->id]);
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

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $companyId = (string)$this->getCurrentUser()->get('company_id');
        $newUser = $UsersTable->newEmptyEntity();
        $counters = $this->fetchCounters($companyId);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if (!empty($data['new_user'])) {
                $newUser = $this->createUserForProject($project, $data, $counters);
                if ($newUser->getErrors() === [] && !$newUser->isNew()) {
                    return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
                }
            } else {
                $projectsUser = $ProjectsUsersTable->patchEntity($projectsUser, $data);

                if ($ProjectsUsersTable->save($projectsUser)) {
                    $this->grantPluginAccess((string)$projectsUser->user_id, $companyId);
                    $this->Flash->success(__d('projects', 'The project user has been saved.'));
                    $redirect = $this->getRequest()->getData('redirect', null);
                    if (!empty($redirect)) {
                        return $this->redirect(base64_decode($redirect));
                    }

                    return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
                }
                $this->Flash->error(__d('projects', 'The project user could not be saved. Please, try again.'));
            }
        }

        $users = $UsersTable->fetchForCompany($companyId);
        foreach ($users as $i => $user) {
            if ($user->hasRole('admin')) {
                unset($users[$i]);
            }
        }

        $this->set(compact('projectsUser', 'users', 'newUser', 'counters'));

        return null;
    }

    /**
     * Access bits for the plugins every project user needs: Projects and Documents (when loaded).
     *
     * @return int
     */
    protected function projectsAccessBits(): int
    {
        $access = 1 << AppPluginsEnum::Projects->getOrdinal();
        if (Plugin::isLoaded('Documents')) {
            $access |= 1 << AppPluginsEnum::Documents->getOrdinal();
        }

        return $access;
    }

    /**
     * Add the missing Projects and Documents plugin access bits to an existing user of the company.
     * Existing bits are kept; document counters are not changed.
     *
     * @param string $userId User id.
     * @param string $companyId Company id of the current user.
     * @return void
     */
    protected function grantPluginAccess(string $userId, string $companyId): void
    {
        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        /** @var \App\Model\Entity\User|null $user */
        $user = $UsersTable->find()->where(['id' => $userId, 'company_id' => $companyId])->first();
        if (!$user) {
            return;
        }

        $access = (int)$user->access | $this->projectsAccessBits();
        if ($access !== (int)$user->access) {
            $user->access = $access;
            $UsersTable->save($user);
        }
    }

    /**
     * Send the login instructions again to a project user, with a new password reset key.
     *
     * @param string $projectId Project id.
     * @param string $userId User id.
     * @return \Cake\Http\Response|null
     */
    public function resendInstructions(string $projectId, string $userId): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $project = $this->Projects->get($projectId);
        $this->Authorization->authorize($project, 'user');

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        /** @var \App\Model\Entity\User|null $user */
        $user = $UsersTable->find()
            ->where(['Users.id' => $userId, 'Users.company_id' => $this->getCurrentUser()->get('company_id')])
            ->first();
        /** @var \Projects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
        $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');
        $linked = $ProjectsUsersTable->hasAccess($projectId, $userId);

        if (!$user || !$linked) {
            $this->Flash->error(__d('projects', 'No link found.'));
        } else {
            $user->reset_key = bin2hex(random_bytes(16));
            if ($UsersTable->save($user) && $UsersTable->sendWelcomeEmail($user)) {
                $this->Flash->success(__d('projects', 'The login instructions have been sent.'));
            } else {
                $this->Flash->error(__d('projects', 'The login instructions could not be sent.'));
            }
        }

        return $this->redirect(['action' => 'view', $project->id, '?' => ['tab' => 'users']]);
    }

    /**
     * Fetch active document counters of the company for the multiselect.
     *
     * @param string $companyId Company id.
     * @return array<string, string> Counter titles indexed by counter id.
     */
    protected function fetchCounters(string $companyId): array
    {
        if (!Plugin::isLoaded('Documents')) {
            return [];
        }

        /** @var array<string, string> $counters */
        $counters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters')
            ->find('list', keyField: 'id', valueField: 'title')
            ->where(['DocumentsCounters.owner_id' => $companyId, 'DocumentsCounters.active' => true])
            ->orderBy(['DocumentsCounters.title'])
            ->toArray();

        return $counters;
    }

    /**
     * Create a new user with access to the project, the Projects and Documents plugins and chosen
     * document counters, then send the login instructions by email.
     *
     * Returned entity has no errors and is not new when the user was saved.
     *
     * @param \Projects\Model\Entity\Project $project Project the user is given access to.
     * @param array<string, mixed> $data Submitted form data.
     * @param array<string, string> $counters Counters the user may choose from, indexed by id.
     * @return \App\Model\Entity\User
     */
    protected function createUserForProject(Project $project, array $data, array $counters): User
    {
        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');

        $privileges = (int)($data['new_privileges'] ?? 10);
        $user = $UsersTable->newEntity([
            'company_id' => $this->getCurrentUser()->get('company_id'),
            'name' => trim((string)($data['new_name'] ?? '')),
            'email' => trim((string)($data['new_email'] ?? '')),
            'username' => trim((string)($data['new_email'] ?? '')),
            'privileges' => in_array($privileges, [7, 10, 15], true) ? $privileges : 10,
            'access' => $this->projectsAccessBits(),
            'active' => true,
            'reset_key' => bin2hex(random_bytes(16)),
        ]);

        if ($user->getErrors() !== []) {
            $this->Flash->error(__d('projects', 'The user could not be created. Please, try again.'));

            return $user;
        }

        $counterIds = array_values(array_intersect(
            array_map('strval', (array)($data['new_counters'] ?? [])),
            array_map('strval', array_keys($counters)),
        ));

        $saved = $UsersTable->getConnection()->transactional(
            function () use ($UsersTable, $ProjectsUsersTable, $user, $project, $counterIds) {
                if (!$UsersTable->save($user)) {
                    return false;
                }

                $projectsUser = $ProjectsUsersTable->newEntity(['project_id' => $project->id, 'user_id' => $user->id]);
                if (!$ProjectsUsersTable->save($projectsUser)) {
                    return false;
                }

                if ($counterIds) {
                    $CountersUsers = TableRegistry::getTableLocator()
                        ->get('DocumentsCountersUsers', ['table' => 'documents_counters_users']);
                    foreach ($counterIds as $counterId) {
                        $link = $CountersUsers->newEntity(['counter_id' => $counterId, 'user_id' => $user->id]);
                        if (!$CountersUsers->save($link)) {
                            return false;
                        }
                    }
                }

                return true;
            },
        );

        if (!$saved) {
            $user->setNew(true);
            $this->Flash->error(__d('projects', 'The user could not be created. Please, try again.'));

            return $user;
        }

        if ($UsersTable->sendWelcomeEmail($user)) {
            $this->Flash->success(__d(
                'projects',
                'The user has been created and an email with login instructions has been sent.',
            ));
        } else {
            $this->Flash->warning(__d(
                'projects',
                'The user has been created, but the email with login instructions could not be sent.',
            ));
        }

        return $user;
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
}
