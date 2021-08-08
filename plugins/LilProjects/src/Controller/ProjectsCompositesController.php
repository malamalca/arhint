<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use LilProjects\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * ProjectsComposites Controller
 *
 * @property \LilProjects\Model\Table\ProjectsCompositesTable $ProjectsComposites
 * @method \LilProjects\Model\Entity\ProjectsComposite[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
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

        $this->Authorization->Authorize($composite);

        $this->set(compact('composite'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->setAction('edit');
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
                $this->Flash->success(__('The projects composite has been saved.'));

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
            $this->Flash->error(__('The projects composite could not be saved. Please, try again.'));
        }

        $project = TableRegistry::getTableLocator()->get('LilProjects.Projects')->get($projectsComposite->project_id);

        $this->set(compact('projectsComposite', 'project'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Composite id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsComposite = $this->ProjectsComposites->get($id);

        $this->Authorization->Authorize($projectsComposite);

        if ($this->ProjectsComposites->delete($projectsComposite)) {
            $this->Flash->success(__('The projects composite has been deleted.'));
        } else {
            $this->Flash->error(__('The projects composite could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'controller' => 'Projects', 
            'action' => 'view', 
            $projectsComposite->project_id, 
            '?' => ['tab' => 'composites']]);
    }
}
