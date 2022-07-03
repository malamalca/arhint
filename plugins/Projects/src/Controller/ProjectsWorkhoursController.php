<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Event\EventInterface;
use Cake\I18n\FrozenTime;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

/**
 * ProjectsWorkhours Controller
 *
 * @property \Projects\Model\Table\ProjectsWorkhoursTable $ProjectsWorkhours
 * @method \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet paginate($object = null, array $settings = [])
 */
class ProjectsWorkhoursController extends AppController
{
    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!empty($this->Security)) {
            if (in_array($this->getRequest()->getParam('action'), ['import'])) {
                $this->Security->setConfig('validatePost', false);
            }
        }

        return null;
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();
        if (!$this->getCurrentUser()->hasRole('admin')) {
            $filter['user'] = $this->getRequest()->getQuery('user');
        }

        $params = $this->ProjectsWorkhours->filter($filter);
        $query = $this->Authorization->applyScope($this->ProjectsWorkhours->find(), 'index')
            ->where($params['conditions']);

        $sumQuery = clone $query;
        $totalDuration = $sumQuery
            ->select([
                'sumDurations' => $sumQuery->func()->sum('duration'),
            ])
            ->disableHydration()
            ->first();
        $totalDuration = $totalDuration['sumDurations'];

        $projectsWorkhours = $this->paginate($query, ['order' => ['started' => 'DESC']]);

        if ($this->getCurrentUser()->hasRole('admin')) {
            /** @var \App\Model\Table\UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
            $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));
        } else {
            $users[$this->getCurrentUser()->get('id')] = $this->getCurrentUser()->getOriginalData();
        }

        $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find(), 'index')
            ->where(['active' => true])
            ->order(['no DESC', 'title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        $this->set(compact('projectsWorkhours', 'filter', 'users', 'projects', 'totalDuration'));
    }

    /**
     * List method
     *
     * @return \Cake\Http\Response|null
     */
    public function list()
    {
        $sourceUrl = $this->getRequest()->getQuery('source');
        $sourceRequest = [];
        if (!empty($sourceUrl)) {
            $request = new \Cake\Http\ServerRequest(['url' => $this->getRequest()->getQuery('source')]);
            $sourceRequest = Router::parseRequest($request);

            $sourceRequest = array_merge($sourceRequest, $sourceRequest['pass']);
            unset($sourceRequest['_matchedRoute']);
            unset($sourceRequest['pass']);
        }

        $filter = [];
        $filter['project'] = $sourceRequest['pass'][0] ?? null;

        $params = $this->ProjectsWorkhours->filter($filter);

        $query = $this->Authorization->applyScope($this->ProjectsWorkhours->find(), 'index')
            ->select(['id', 'project_id', 'user_id', 'started', 'duration', 'dat_confirmed'])
            ->where($params['conditions']);

        $data = $this->paginate($query, ['limit' => 5, 'order' => ['started' => 'desc']]);

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'), ['inactive' => true]);

        $this->set(compact('data', 'sourceRequest', 'users'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Workhour id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
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
                $this->getRequest()->getData()
            );
            if ($this->ProjectsWorkhours->save($projectsWorkhour)) {
                $this->Flash->success(__d('projects', 'The projects workhour has been saved.'));

                $referer = $this->getRequest()->getData('referer');

                return $this->redirect(
                    $referer ?? ['action' => 'index', '?' => ['project' => $projectsWorkhour->project_id]]
                );
            }
            $this->Flash->error(__d('projects', 'The projects workhour could not be saved. Please, try again.'));
        }

        $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find(), 'index')
            ->where(['active' => true])
            ->order(['no DESC', 'title'])
            ->all()
            ->combine('id', function ($entity) {
                return $entity;
            })
            ->toArray();

        $this->set(compact('projectsWorkhour', 'projects'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Workhour id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['post', 'delete', 'get']);
        $projectsWorkhour = $this->ProjectsWorkhours->get($id);
        $this->Authorization->authorize($projectsWorkhour);
        if ($this->ProjectsWorkhours->delete($projectsWorkhour)) {
            $this->Flash->success(__d('projects', 'The projects workhour has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects workhour could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->referer());
    }

    /**
     * Import from timetracking software
     *
     * @return \Cake\Http\Response|null
     */
    public function import()
    {
        $this->getRequest()->allowMethod(['post']);

        $data = $this->getRequest()->getData('data');
        if (!empty($data)) {
            $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find('list'), 'index')
                ->select()
                ->where(['active' => true])
                ->order('title')
                ->toArray();

            foreach ((array)$data as $registration) {
                if (
                    !empty($registration['project_id']) &&
                    isset($projects[$registration['project_id']]) &&
                    !empty($registration['datetime']) &&
                    FrozenTime::parseDateTime($registration['datetime'], 'yyyy-MM-dd HH:mm:ss')
                ) {
                    $registrationTime = FrozenTime::parseDateTime($registration['datetime'], 'yyyy-MM-dd HH:mm:ss');

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
                                    ->order('started DESC')
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
}
