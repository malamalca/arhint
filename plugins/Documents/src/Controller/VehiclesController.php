<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Http\Response;

/**
 * Vehicles Controller
 *
 * @property \Documents\Model\Table\VehiclesTable $Vehicles
 */
class VehiclesController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void Renders view
     */
    public function index()
    {
        $vehicles = $this->Authorization->applyScope($this->Vehicles->find())
            ->orderBy('Vehicles.title')
            ->all();
        $this->set(compact('vehicles'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Vehicle id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $vehicle = $this->Vehicles->newEmptyEntity();
            $vehicle->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $vehicle = $this->Vehicles->get($id);
        }

        $this->Authorization->authorize($vehicle);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $vehicle = $this->Vehicles->patchEntity($vehicle, $this->getRequest()->getData());
            $vehicle->owner_id = $this->getCurrentUser()->get('company_id');

            if ($this->Vehicles->save($vehicle)) {
                $this->Flash->success(__d('documents', 'The vehicles has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'The vehicles could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('vehicle'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Vehicle id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $vehicle = $this->Vehicles->get($id);
        $this->Authorization->authorize($vehicle);
        if ($this->Vehicles->delete($vehicle)) {
            $this->Flash->success(__d('documents', 'The vehicle has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The vehicle could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
