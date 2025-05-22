<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Http\Response;

/**
 * Vats Controller
 *
 * @property \Documents\Model\Table\VatsTable $Vats
 */
class VatsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $vats = $this->Authorization->applyScope($this->Vats->find())
            ->orderBy('descript')
            ->all();
        $this->set(compact('vats'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Vat id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $vat = $this->Vats->newEmptyEntity();
            $vat->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $vat = $this->Vats->get($id);
        }

        $this->Authorization->authorize($vat);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $vat = $this->Vats->patchEntity($vat, $this->getRequest()->getData());
            $vat->owner_id = $this->getCurrentUser()->get('company_id');

            if ($this->Vats->save($vat)) {
                $this->Flash->success(__d('documents', 'The vat has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'The vat could not be saved. Please, try again.'));
            }
        }
        $this->set(compact('vat'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Vat id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $vat = $this->Vats->get($id);
        $this->Authorization->authorize($vat);

        if ($this->Vats->delete($vat)) {
            $this->Flash->success(__d('documents', 'The vat has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The vat could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
