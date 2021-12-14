<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use Cake\ORM\TableRegistry;
use LilProjects\Lib\LilProjectsFuncs;

/**
 * ProjectsComposites Controller
 *
 * @property \LilProjects\Model\Table\ProjectsCompositesTable $ProjectsComposites
 * @method \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet paginate($object = null, array $settings = [])
 */
class ProjectsCompositesController extends AppController
{
    /**
     * View method
     *
     * @param string|null $id Projects Composite id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $composite = $this->ProjectsComposites->get($id, [
            'contain' => ['CompositesMaterials'],
        ]);

        $ProjectsTable = TableRegistry::getTableLocator()->get('LilProjects.Projects');
        $project = $ProjectsTable->get($composite->project_id);

        $this->Authorization->Authorize($composite);

        $this->set(compact('composite', 'project'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Composite id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $projectsComposite = $this->ProjectsComposites->get($id);
        } else {
            $projectsComposite = $this->ProjectsComposites->newEmptyEntity();
            $projectsComposite->project_id = $this->getRequest()->getQuery('project');
        }

        $this->Authorization->Authorize($projectsComposite);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $projectsComposite = $this->ProjectsComposites->patchEntity($projectsComposite, $this->request->getData());
            if ($this->ProjectsComposites->save($projectsComposite)) {
                $this->Flash->success(__d('lil_projects', 'The projects composite has been saved.'));

                $redirect = $this->getRequest()->getData('redirect');
                if (!empty($redirect)) {
                    return $this->redirect(base64_decode($redirect));
                }

                return $this->redirect([
                    'controller' => 'Projects',
                    'action' => 'view',
                    $projectsComposite->project_id,
                    '?' => ['tab' => 'composites']]);
            }
            $this->Flash->error(__d('lil_projects', 'The projects composite could not be saved. Please, try again.'));
        }

        $project = TableRegistry::getTableLocator()->get('LilProjects.Projects')->get($projectsComposite->project_id);

        $this->set(compact('projectsComposite', 'project'));
    }

    /**
     * Delete method
     *
     * @param string $id Projects Composite id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsComposite = $this->ProjectsComposites->get($id);

        $this->Authorization->Authorize($projectsComposite);

        if ($this->ProjectsComposites->delete($projectsComposite)) {
            $this->Flash->success(__d('lil_projects', 'The projects composite has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_projects', 'The projects composite could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'controller' => 'Projects',
            'action' => 'view',
            $projectsComposite->project_id,
            '?' => ['tab' => 'composites'],
        ]);
    }

    /**
     * Export method
     *
     * @param string $projectId Projects id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function export($projectId)
    {
        /** @var \LilProjects\Model\Entity\Project $project */
        $project = TableRegistry::getTableLocator()->get('LilProjects.Projects')->get($projectId);

        $this->Authorization->Authorize($project, 'view');

        $projectsComposites = $this->ProjectsComposites->find()
            ->select()
            ->contain(['CompositesMaterials'])
            ->where(['project_id' => $project->id])
            ->all();

        $data = LilProjectsFuncs::exportComposites2Word($projectsComposites);

        $response = $this->response;
        $response = $response->withStringBody((string)$data);

        $response = $response->withType('application/vnd.openxmlformats-officedocument.wordprocessingml');

        // Optionally force file download
        $response = $response->withDownload($project->title . '-Composites.docx');

        // Return response object to prevent controller from trying to render
        // a view.
        return $response;
    }
}
