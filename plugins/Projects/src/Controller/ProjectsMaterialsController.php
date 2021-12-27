<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Core\Configure;

/**
 * ProjectsMaterials Controller
 *
 * @property \Projects\Model\Table\ProjectsMaterialsTable $ProjectsMaterials
 * @method \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet paginate($object = null, array $settings = [])
 */
class ProjectsMaterialsController extends AppController
{
    /**
     * Initialize function
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Paginator');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Authorization->applyScope($this->ProjectsMaterials->find());

        $searchStr = $this->getRequest()->getQuery('search');
        if (!empty($searchStr)) {
            $query->where(['descript LIKE' => '%' . $searchStr . '%']);
        }

        $projectsMaterials = $this->Paginator->paginate($query, ['limit' => 20]);

        $groups = Configure::read('Projects.materialGroups');

        $this->set(compact('projectsMaterials', 'groups'));
    }

    /**
     * Lookup method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function lookup()
    {
        $query = $this->Authorization->applyScope($this->ProjectsMaterials->find(), 'index');

        $searchStr = $this->getRequest()->getQuery('search');
        if (!empty($searchStr)) {
            $query->where(['descript LIKE' => '%' . $searchStr . '%']);
        }

        $projectsMaterials = $this->Paginator->paginate($query, ['limit' => 20]);

        $groups = Configure::read('Projects.materialGroups');

        $this->set(compact('projectsMaterials', 'groups'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Projects Material id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if (!empty($id)) {
            $projectsMaterial = $this->ProjectsMaterials->get($id);
        } else {
            $projectsMaterial = $this->ProjectsMaterials->newEmptyEntity();
            $projectsMaterial->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($projectsMaterial);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $projectsMaterial = $this->ProjectsMaterials->patchEntity($projectsMaterial, $this->request->getData());
            if ($this->ProjectsMaterials->save($projectsMaterial)) {
                $this->Flash->success(__d('projects', 'The projects material has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__d('projects', 'The projects material could not be saved. Please, try again.'));
        }

        $groups = Configure::read('Projects.materialGroups');

        $this->set(compact('projectsMaterial', 'groups'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Material id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $projectsMaterial = $this->ProjectsMaterials->get($id);
        $this->Authorization->authorize($projectsMaterial);

        if ($this->ProjectsMaterials->delete($projectsMaterial)) {
            $this->Flash->success(__d('projects', 'The projects material has been deleted.'));
        } else {
            $this->Flash->error(__d('projects', 'The projects material could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
