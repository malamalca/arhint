<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Response;
use Expenses\Model\Entity\BookingRule;

/**
 * BookingRules Controller
 *
 * @property \Expenses\Model\Table\BookingRulesTable $BookingRules
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BookingRulesController extends AppController
{
    /**
     * Index method – list booking rules with optional model filter.
     *
     * @return void
     */
    public function index(): void
    {
        $ownerId = $this->getCurrentUser()->get('company_id');

        $query = $this->Authorization->applyScope($this->BookingRules->find())
            ->select(['id', 'model', 'title', 'created'])
            ->contain([
                'BookingRuleFilters',
                'BookingRuleAccountEntries' => ['Accounts'],
            ])
            ->where(['BookingRules.owner_id' => $ownerId])
            ->orderBy(['BookingRules.model' => 'ASC', 'BookingRules.title' => 'ASC']);

        $data = $this->paginate($query);
        $modelList = $this->BookingRules->modelList();

        $this->set(compact('data', 'modelList'));
    }

    /**
     * View method – display a booking rule with filters and account entries.
     *
     * @param string|null $id BookingRule id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $bookingRule = $this->BookingRules->get(
            $id,
            contain: [
                'BookingRuleFilters',
                'BookingRuleAccountEntries' => ['Accounts'],
            ],
        );

        $this->Authorization->authorize($bookingRule);

        $modelLabels = BookingRule::modelLabels();

        $this->set(compact('bookingRule', 'modelLabels'));
    }

    /**
     * Edit method – add or edit a booking rule.
     *
     * @param string|null $id BookingRule id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $bookingRule = $this->BookingRules->get($id);
        } else {
            $bookingRule = $this->BookingRules->newEmptyEntity();
        }

        $this->Authorization->authorize($bookingRule);

        $modelList = $this->BookingRules->modelList();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if ($bookingRule->isNew()) {
                $data['owner_id'] = $this->getCurrentUser()->get('company_id');
            }

            $bookingRule = $this->BookingRules->patchEntity($bookingRule, $data);

            if ($this->BookingRules->save($bookingRule)) {
                $this->Flash->success(__d('expenses', 'The booking rule has been saved.'));

                return $this->redirect(['action' => 'view', $bookingRule->id]);
            } else {
                $this->Flash->error(__d('expenses', 'The booking rule could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('bookingRule', 'modelList'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id BookingRule id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $bookingRule = $this->BookingRules->get($id);
        $this->Authorization->authorize($bookingRule);

        if ($this->BookingRules->delete($bookingRule)) {
            $this->Flash->success(__d('expenses', 'The booking rule has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The booking rule could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
