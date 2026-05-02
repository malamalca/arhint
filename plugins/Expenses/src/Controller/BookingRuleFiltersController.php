<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Response;
use Expenses\Model\Entity\BookingRuleFilter;

/**
 * BookingRuleFilters Controller
 *
 * @property \Expenses\Model\Table\BookingRuleFiltersTable $BookingRuleFilters
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BookingRuleFiltersController extends AppController
{
    /**
     * Edit method – add or edit a booking rule filter.
     *
     * @param string|null $id BookingRuleFilter id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $filter = $this->BookingRuleFilters->get($id, contain: ['BookingRules']);
        } else {
            $filter = $this->BookingRuleFilters->newEmptyEntity();
        }

        $this->Authorization->authorize($filter);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if ($filter->isNew() && !empty($data['rule_id'])) {
                $data['sort'] = $this->BookingRuleFilters->nextSort($data['rule_id']);
            }

            if (isset($data['end_operator']) && $data['end_operator'] === '') {
                $data['end_operator'] = null;
            }

            $filter = $this->BookingRuleFilters->patchEntity($filter, $data);

            if ($this->BookingRuleFilters->save($filter)) {
                $this->Flash->success(__d('expenses', 'The filter has been saved.'));

                if ($this->getRequest()->is('ajax')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode(['success' => true]) ?: '{}');
                }

                return $this->redirect([
                    'controller' => 'BookingRules',
                    'action' => 'view',
                    $filter->rule_id,
                ]);
            } else {
                $this->Flash->error(__d('expenses', 'The filter could not be saved. Please, try again.'));
            }
        }

        // Pre-fill rule_id from query string when creating a new filter
        $ruleId = $this->getRequest()->getQuery('rule_id');
        if ($filter->isNew() && $ruleId) {
            $filter->rule_id = $ruleId;
        }

        $operatorList = BookingRuleFilter::operatorLabels();
        $endOperatorList = [
            '' => __d('expenses', '— end —'),
            'and' => __d('expenses', 'AND'),
            'or' => __d('expenses', 'OR'),
        ];

        $this->set(compact('filter', 'operatorList', 'endOperatorList'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id BookingRuleFilter id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $filter = $this->BookingRuleFilters->get($id, contain: ['BookingRules']);
        $this->Authorization->authorize($filter);

        $ruleId = $filter->rule_id;

        if ($this->BookingRuleFilters->delete($filter)) {
            $this->Flash->success(__d('expenses', 'The filter has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The filter could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'controller' => 'BookingRules',
            'action' => 'view',
            $ruleId,
        ]);
    }
}
