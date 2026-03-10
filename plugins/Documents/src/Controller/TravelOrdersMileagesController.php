<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Http\Response;

/**
 * TravelOrdersMileages Controller
 *
 * @property \Documents\Model\Table\TravelOrdersMileagesTable $TravelOrdersMileages
 */
class TravelOrdersMileagesController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id TravelOrdersMileage id.
     * @return \Cake\Http\Response|null
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $mileage = $this->TravelOrdersMileages->newEmptyEntity();
            $mileage->travel_order_id = $this->getRequest()->getQuery('travel_order_id')
                ?? $this->getRequest()->getData('travel_order_id');
        } else {
            $mileage = $this->TravelOrdersMileages->get($id);
        }

        $this->Authorization->authorize($mileage);

        $redirect = $this->getRequest()->getQuery('redirect');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $mileage = $this->TravelOrdersMileages->patchEntity($mileage, $this->getRequest()->getData());
            $mileage->total = round((float)($mileage->distance_km ?? 0) * (float)($mileage->price_per_km ?? 0), 2);

            if ($this->TravelOrdersMileages->save($mileage)) {
                $this->Flash->success(__d('documents', 'The mileage entry has been saved.'));

                $redirect = $this->getRequest()->getData('redirect') ?: $redirect;
                if ($redirect) {
                    return $this->redirect($redirect);
                }

                return $this->redirect([
                    'plugin' => 'Documents',
                    'controller' => 'TravelOrders',
                    'action' => 'view',
                    $mileage->travel_order_id,
                ]);
            } else {
                $this->Flash->error(__d('documents', 'The mileage entry could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('mileage', 'redirect'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id TravelOrdersMileage id.
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $mileage = $this->TravelOrdersMileages->get($id);
        $this->Authorization->authorize($mileage);

        $redirect = $this->getRequest()->getQuery('redirect');

        if ($this->TravelOrdersMileages->delete($mileage)) {
            $this->Flash->success(__d('documents', 'The mileage entry has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The mileage entry could not be deleted. Please, try again.'));
        }

        if ($redirect) {
            return $this->redirect($redirect);
        }

        return $this->redirect([
            'plugin' => 'Documents',
            'controller' => 'TravelOrders',
            'action' => 'view',
            $mileage->travel_order_id,
        ]);
    }
}
