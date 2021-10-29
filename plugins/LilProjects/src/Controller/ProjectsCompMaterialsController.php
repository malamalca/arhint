<?php
declare(strict_types=1);

namespace LilProjects\Controller;

use Cake\ORM\TableRegistry;

/**
 * ProjectsCompMaterials Controller
 *
 * @property \LilProjects\Model\Table\ProjectsCompMaterialsTable $ProjectsCompMaterials
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class ProjectsCompMaterialsController extends AppController
{
    /**
     * View method
     *
     * @param string|null $id Projects Comp Material id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $material = $this->ProjectsCompMaterials->get($id);

        $this->Authorization->Authorize($material);

        $this->set(compact('material'));
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
     * @param string|null $id Projects Comp Material id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $material = $this->ProjectsCompMaterials->get($id);
        } else {
            $material = $this->ProjectsCompMaterials->newEmptyEntity();
            $material->composite_id = $this->getRequest()->getQuery('composite');
            $material->sort_order = (int)$this->getRequest()->getQuery('order');
            $material->is_group = (bool)$this->getRequest()->getQuery('group');
        }

        $this->Authorization->Authorize($material);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $material = $this->ProjectsCompMaterials->patchEntity($material, $this->request->getData());

            if ($this->ProjectsCompMaterials->save($material)) {
                if ($this->getRequest()->is('json')) {
                    $this->autoRender = false;
                    header('Content-Type: application/json');
                    echo json_encode(['result' => $material]);

                    return;
                }

                $this->Flash->success(__d('lil_projects', 'The projects comp material has been saved.'));

                return $this->redirect([
                    'controller' => 'ProjectsComposites',
                    'action' => 'view',
                    $material->composite_id,
                ]);
            }
            $this->Flash->error(__d('lil_projects', 'The material could not be saved. Please, try again.'));
        }

        $ProjectsCompositesTable = TableRegistry::getTableLocator()->get('LilProjects.ProjectsComposites');
        $composite = $ProjectsCompositesTable->get($material->composite_id);

        $this->set(compact('material', 'composite'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Projects Comp Material id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $material = $this->ProjectsCompMaterials->get($id);

        $this->Authorization->Authorize($material);

        if ($this->ProjectsCompMaterials->delete($material)) {
            if ($this->getRequest()->is('json') || $this->getRequest()->is('aht')) {
                $this->autoRender = false;

                return;
            }
            $this->Flash->success(__d('lil_projects', 'The material has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_projects', 'The material could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'ProjectsComposites', 'action' => 'view', $material->composite_id]);
    }

    /**
     * Reorder materials
     *
     * @param string|null $id Projects Comp Material id.
     * @param int $position New position inside composite
     * @return void
     */
    public function reorder($id = null, $position = null)
    {
        $material = $this->ProjectsCompMaterials->get($id);
        $this->Authorization->Authorize($material, 'edit');

        if (!empty($id) && $this->ProjectsCompMaterials->reorder($material, $position)) {
            $this->autoRender = false;
        }
    }
}
