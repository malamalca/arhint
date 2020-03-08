<?php
declare(strict_types=1);

namespace LilProjects\Controller;

/**
 * ProjectsWorkhours Controller
 *
 * @property \LilProjects\Model\Table\ProjectsWorkhoursTable $ProjectsWorkhours
 *
 * @method \LilProjects\Model\Entity\ProjectsWorkhour[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsWorkhoursController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->Authorization->skipAuthorization();
        $this->paginate = [
            'contain' => ['Users'],
        ];

        $projectsWorkhours = $this->paginate($this->ProjectsWorkhours);

        $project = $this->ProjectsWorkhours->Projects->get($this->getRequest()->getQuery('project'));

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

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('lil_projects', 'The projects workhour could not be saved. Please, try again.'));
        }
        $this->set(compact('projectsWorkhour'));

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

        return $this->redirect(['action' => 'index']);
    }
}
