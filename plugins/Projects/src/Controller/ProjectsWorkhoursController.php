<?php
declare(strict_types=1);

namespace Projects\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Projects\Filter\ProjectsWorkhoursFilter;

/**
 * ProjectsWorkhours Controller
 *
 * @property \Projects\Model\Table\ProjectsWorkhoursTable $ProjectsWorkhours
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class ProjectsWorkhoursController extends AppController
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

        if ($this->getRequest()->getParam('action') == 'import') {
            $this->FormProtection->setConfig('validate', false);
        }

        $this->FormProtection->setConfig('unlockedActions', ['bulk']);
    }

    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $filter = new ProjectsWorkhoursFilter($this->getRequest()->getQuery('q', ''));
        $params = $filter->getParams($this->getCurrentUser());

        $query = $this->Authorization->applyScope($this->ProjectsWorkhours->find(), 'index')
            ->select($this->ProjectsWorkhours)
            ->select(['Users.id', 'Users.name']) // User can be inactive so it wont be included in users dropdown
            ->select(['Projects.id', 'Projects.no', 'Projects.title'])
            ->contain(['Users', 'Projects'])
            ->where($params['conditions']);

        $projectsWorkhours = $this->paginate($query, [
            'order' => $params['order'] ?? ['ProjectsWorkhours.created DESC'],
            'limit' => 20,
        ]);

        $workhourCount = $this->ProjectsWorkhours->find('workhoursCount', $this->getCurrentUser(), clone $filter)
            ->first()
            ->toArray();

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find(), 'index')
            ->where(['active' => true])
            ->orderBy(['no DESC', 'title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        $this->set(compact('projectsWorkhours', 'filter', 'users', 'projects', 'workhourCount'));
    }

    /**
     * List method
     *
     * @return void
     */
    public function list()
    {
        $sourceUrl = $this->getRequest()->getQuery('source');
        $sourceRequest = [];
        if (!empty($sourceUrl)) {
            $request = new ServerRequest(['url' => $this->getRequest()->getQuery('source')]);
            $sourceRequest = Router::parseRequest($request);

            $sourceRequest = array_merge($sourceRequest, $sourceRequest['pass']);
            unset($sourceRequest['_matchedRoute']);
            unset($sourceRequest['pass']);
        }

        $filter = [];
        $filter['project'] = $sourceRequest[0] ?? null;

        $params = $this->ProjectsWorkhours->filter($filter);

        $query = $this->Authorization->applyScope($this->ProjectsWorkhours->find(), 'index')
            ->select(['id', 'project_id', 'user_id', 'started', 'duration', 'dat_confirmed'])
            ->where($params['conditions']);

        $data = $this->paginate($query, ['limit' => 5, 'order' => ['started' => 'desc']]);

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'), ['inactive' => true]);

        $this->set(compact('data', 'sourceRequest', 'users'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Workhour id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $projectsWorkhour = $this->ProjectsWorkhours->get($id);
        } else {
            $projectsWorkhour = $this->ProjectsWorkhours->newEmptyEntity();
            $projectsWorkhour->user_id = $this->getCurrentUser()->get('id');
            $projectsWorkhour->project_id = $this->getRequest()->getQuery('project');
        }
        $this->Authorization->authorize($projectsWorkhour);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $projectsWorkhour = $this->ProjectsWorkhours->patchEntity(
                $projectsWorkhour,
                $this->getRequest()->getData(),
            );
            if ($this->ProjectsWorkhours->save($projectsWorkhour)) {
                $this->Flash->success(__d('projects', 'The projects workhour has been saved.'));

                $redirect = $this->getRequest()->getData(
                    'referer',
                    ['action' => 'index', '?' => ['project' => $projectsWorkhour->project_id]],
                );

                return $this->redirect($redirect);
            }
            $this->Flash->error(__d('projects', 'The projects workhour could not be saved. Please, try again.'));
        }

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany(
            $this->getCurrentUser()->get('company_id'),
            ['includeUsers' => $projectsWorkhour->user_id],
        );

        $projectsFilter = ['active' => true];
        if (!empty($projectsWorkhour->project_id)) {
            $projectsFilter = ['OR' => ['active' => true, 'id' => $projectsWorkhour->project_id]];
        }
        $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find(), 'index')
            ->where($projectsFilter)
            ->orderBy(['no DESC', 'title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        $this->set(compact('projectsWorkhour', 'projects', 'users'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Workhour id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete', 'get']);
        $projectsWorkhour = $this->ProjectsWorkhours->get($id);
        $this->Authorization->authorize($projectsWorkhour);

        if ($this->ProjectsWorkhours->delete($projectsWorkhour)) {
            $this->Flash->success(__d('projects', 'The projects workhour has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects workhour could not be deleted. Please, try again.'));
        }

        return $this->redirect(
            $this->getRequest()->referer() ??
            ['action' => 'index', '?' => ['project' => $projectsWorkhour->project_id]],
        );
    }

    /**
     * Import from timetracking software
     *
     * @return \Cake\Http\Response
     */
    public function import(): Response
    {
        $this->getRequest()->allowMethod(['post']);

        $data = $this->getRequest()->getData('data');
        if (!empty($data)) {
            $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find('list'), 'index')
                ->select()
                ->where(['active' => true])
                ->orderBy('title')
                ->toArray();

            foreach ((array)$data as $registration) {
                if (
                    !empty($registration['project_id']) &&
                    isset($projects[$registration['project_id']]) &&
                    !empty($registration['datetime']) &&
                    DateTime::parseDateTime($registration['datetime'], 'yyyy-MM-dd HH:mm:ss')
                ) {
                    $registrationTime = DateTime::parseDateTime($registration['datetime'], 'yyyy-MM-dd HH:mm:ss');

                    switch ($registration['mode']) {
                        case 'start':
                            $workhour = $this->ProjectsWorkhours->newEmptyEntity();
                            $workhour->user_id = $this->getCurrentUser()->get('id');
                            $workhour->project_id = $registration['project_id'];
                            $workhour->started = $registrationTime;
                            $this->ProjectsWorkhours->save($workhour);
                            break;
                        case 'stop':
                            if (!empty($workhour)) {
                                // previously saved workhour in the same loop exists
                                if ($workhour->project_id != $registration['project_id']) {
                                    unset($workhour);
                                }
                            } else {
                                // select last workhour
                                /** @var \Projects\Model\Entity\ProjectsWorkhour $workhour */
                                $workhour = $this->ProjectsWorkhours->find()
                                    ->select()
                                    ->where(['user_id' => $this->getCurrentUser()->get('id')])
                                    ->orderBy('started DESC')
                                    ->limit(1)
                                    ->first();
                                if ($workhour->project_id != $registration['project_id']) {
                                    unset($workhour);
                                }
                            }
                            // calculate work duration
                            if (!empty($workhour)) {
                                $workhour->duration = $registrationTime->diffInSeconds($workhour->started);
                                $this->ProjectsWorkhours->save($workhour);
                                unset($workhour);
                            }
                            break;
                    }
                }
            }
        }

        return $this->getResponse();
    }

    /**
     * Bulk action method
     *
     * @return \Cake\Http\Response|null
     */
    public function bulk(): ?Response
    {
        $this->getRequest()->allowMethod(['post']);

        $action = $this->getRequest()->getData('action');
        $ids = (array)$this->getRequest()->getData('ids');

        if (!in_array($action, ['delete', 'approve']) || empty($ids)) {
            $this->Authrorization->skipAuthorization();
            $this->Flash->error(__d('projects', 'Invalid bulk action or no idsselected.'));

            return $this->redirect($this->getRequest()->getData('redirect') ?? ['action' => 'index']);
        }

        $bulkCount = 0;
        switch ($action) {
            case 'delete':
                $workhours = $this->ProjectsWorkhours->find()
                    ->where(['id IN' => $ids])
                    ->all();

                foreach ($workhours as $workhour) {
                    $this->Authorization->authorize($workhour, 'delete');
                    if ($this->ProjectsWorkhours->delete($workhour)) {
                        $bulkCount++;
                    }
                }
                break;
            case 'approve':
                $workhours = $this->ProjectsWorkhours->find()
                    ->where(['id IN' => $ids, 'dat_confirmed IS' => null])
                    ->all();

                foreach ($workhours as $workhour) {
                    $this->Authorization->authorize($workhour, 'edit');
                    $workhour->dat_confirmed = DateTime::now();
                    if ($this->ProjectsWorkhours->save($workhour)) {
                        $bulkCount++;
                    }
                }
                break;
        }

        if ($bulkCount > 0) {
            $this->Flash->success(__d('projects', '{0} workhours have been modified or deleted.', $bulkCount));
        } else {
            $this->Flash->error(__d('projects', 'No workhours have been updated. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->getData('redirect') ?? ['action' => 'index']);
    }
}
