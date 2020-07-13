<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use Cake\Event\EventInterface;
use Cake\I18n\Time;

/**
 * ProjectsWorkhours Controller
 *
 * @property \LilProjects\Model\Table\ProjectsWorkhoursTable $ProjectsWorkhours
 * @method \LilProjects\Model\Entity\ProjectsWorkhour[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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
        $project = $this->ProjectsWorkhours->Projects->get($this->getRequest()->getQuery('project'));
        $this->Authorization->authorize($project, 'view');

        $query = $this->ProjectsWorkhours->find()
            ->where(['project_id' => $project->id])
            ->contain(['Users'])
            ->order(['started DESC']);

        $projectsWorkhours = $this->paginate($query);

        $this->set(compact('projectsWorkhours', 'project'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        return $this->setAction('edit');
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
                $this->Flash->success(__d('lil_projects', 'The projects workhour has been saved.'));

                return $this->redirect(['action' => 'index', '?' => ['project' => $projectsWorkhour->project_id]]);
            }
            $this->Flash->error(__d('lil_projects', 'The projects workhour could not be saved. Please, try again.'));
        }

        $projects = $this->Authorization->applyScope($this->ProjectsWorkhours->Projects->find(), 'index')
            ->where(['active' => true])
            ->order(['no DESC', 'title'])
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
            $this->Flash->success(__d('lil_projects', 'The projects workhour has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_projects', 'The projects workhour could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index', '?' => ['project' => $projectsWorkhour->project_id]]);
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
                    Time::parseDateTime($registration['datetime'], 'yyyy-MM-dd HH:mm:ss')
                ) {
                    $registrationTime = Time::parseDateTime($registration['datetime'], 'yyyy-MM-dd HH:mm:ss');

                    if ($registration['mode'] == 'start') {
                        $workhour = $this->ProjectsWorkhours->newEmptyEntity();
                        $workhour->user_id = $this->getCurrentUser()->get('id');
                        $workhour->project_id = $registration['project_id'];
                        $workhour->started = $registrationTime;

                        $this->ProjectsWorkhours->save($workhour);
                    }

                    if ($registration['mode'] == 'stop') {
                        if (!empty($workhour)) {
                            // previously saved workhour in the same loop exists
                            if ($workhour->project_id != $registration['project_id']) {
                                unset($workhour);
                            }
                        } else {
                            // select last workhour
                            /** @var \LilProjects\Model\Entity\ProjectsWorkhour $workhour */
                            $workhour = $this->ProjectsWorkhours->find()
                            ->select()
                            ->where(['user_id' => $this->getCurrentUser()->get('id')])
                            ->order('started DESC')
                            ->limit(1)
                            ->first();
                            if (!empty($workhour) && ($workhour->project_id != $registration['project_id'])) {
                                unset($workhour);
                            }
                        }
                        // calculate work duration
                        if (!empty($workhour)) {
                            $workhour->duration = $registrationTime->diffInSeconds($workhour->started);
                            $this->ProjectsWorkhours->save($workhour);
                            unset($workhour);
                        }
                    }
                }
            }
        }

        return $this->getResponse();
    }
}
