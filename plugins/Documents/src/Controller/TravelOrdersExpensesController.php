<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Http\Response;

/**
 * TravelOrdersExpenses Controller
 *
 * @property \Documents\Model\Table\TravelOrdersExpensesTable $TravelOrdersExpenses
 */
class TravelOrdersExpensesController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id TravelOrdersExpense id.
     * @return \Cake\Http\Response|null
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $expense = $this->TravelOrdersExpenses->newEmptyEntity();
            $expense->travel_order_id = $this->getRequest()->getQuery('travel_order_id')
                ?? $this->getRequest()->getData('travel_order_id');
        } else {
            $expense = $this->TravelOrdersExpenses->get($id);
        }

        $this->Authorization->authorize($expense);

        $redirect = $this->getRequest()->getQuery('redirect');

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $expense = $this->TravelOrdersExpenses->patchEntity($expense, $this->getRequest()->getData());
            $expense->total = round((float)($expense->quantity ?? 0) * (float)($expense->price ?? 0), 2);

            if ($this->TravelOrdersExpenses->save($expense)) {
                $this->Flash->success(__d('documents', 'The expense entry has been saved.'));

                $redirect = $this->getRequest()->getData('redirect') ?: $redirect;
                if ($redirect) {
                    return $this->redirect($redirect);
                }

                return $this->redirect([
                    'plugin' => 'Documents',
                    'controller' => 'TravelOrders',
                    'action' => 'view',
                    $expense->travel_order_id,
                ]);
            } else {
                $this->Flash->error(__d('documents', 'The expense entry could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('expense', 'redirect'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id TravelOrdersExpense id.
     * @return \Cake\Http\Response|null
     */
    public function delete(?string $id = null): ?Response
    {
        $expense = $this->TravelOrdersExpenses->get($id);
        $this->Authorization->authorize($expense);

        $redirect = $this->getRequest()->getQuery('redirect');

        if ($this->TravelOrdersExpenses->delete($expense)) {
            $this->Flash->success(__d('documents', 'The expense entry has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The expense entry could not be deleted. Please, try again.'));
        }

        if ($redirect) {
            return $this->redirect($redirect);
        }

        return $this->redirect([
            'plugin' => 'Documents',
            'controller' => 'TravelOrders',
            'action' => 'view',
            $expense->travel_order_id,
        ]);
    }
}
