<?php
declare(strict_types=1);

namespace Crm\Controller;

use Cake\Core\Plugin;
use Cake\ORM\TableRegistry;

/**
 * Adremas Controller
 *
 * @property \Crm\Model\Table\AdremasTable $Adremas
 */
class AdremasController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Adrema id.
     * @return mixed Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            $adrema = $this->Adremas->get($id);
        } else {
            $adrema = $this->Adremas->newEmptyEntity();
            $adrema->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($adrema);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            /** @var \Crm\Model\Entity\Adrema $adrema */
            $adrema = $this->Adremas->patchEntity($adrema, $this->getRequest()->getData());

            if ($this->Adremas->save($adrema)) {
                // copy contacts to duplicated adrema
                $sourceId = (string)$this->getRequest()->getQuery('adrema');
                if (!empty($sourceId)) {
                    $this->Adremas->copyAddresses($sourceId, $adrema->id);
                }
                $this->Flash->success(__d('crm', 'The adrema has been saved.'));

                return $this->redirect(['controller' => 'Labels', 'action' => 'adrema', $adrema->id]);
            } else {
                $this->Flash->error(__d('crm', 'The adrema could not be saved. Please, try again.'));
            }
        }

        $projects = [];
        if (Plugin::isLoaded('Projects')) {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
            $projectsQuery = $this->Authorization->applyScope($ProjectsTable->find(), 'index');
            $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->company_id, $projectsQuery);
        }

        $this->set(compact('adrema', 'projects'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Adrema id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $adrema = $this->Adremas->get($id);
        $this->Authorization->authorize($adrema);

        if ($this->Adremas->delete($adrema)) {
            $this->Flash->success(__d('crm', 'The adrema has been deleted.'));
        } else {
            $this->Flash->error(__d('crm', 'The adrema could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Labels', 'action' => 'adrema']);
    }
}
