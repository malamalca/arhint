<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Response;

/**
 * BookingRuleAccountEntries Controller
 *
 * @property \Expenses\Model\Table\BookingRuleAccountEntriesTable $BookingRuleAccountEntries
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BookingRuleAccountEntriesController extends AppController
{
    /**
     * Edit method – add or edit a booking rule account entry.
     *
     * @param string|null $id BookingRuleAccountEntry id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $entry = $this->BookingRuleAccountEntries->get($id, contain: ['Accounts', 'BookingRules']);
        } else {
            $entry = $this->BookingRuleAccountEntries->newEmptyEntity();
        }

        $this->Authorization->authorize($entry);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if ($entry->isNew() && !empty($data['rule_id'])) {
                $data['sort'] = $this->BookingRuleAccountEntries->nextSort($data['rule_id']);
            }

            $entry = $this->BookingRuleAccountEntries->patchEntity($entry, $data);

            if ($this->BookingRuleAccountEntries->save($entry)) {
                $this->Flash->success(__d('expenses', 'The account entry has been saved.'));

                if ($this->getRequest()->is('ajax')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode(['success' => true]) ?: '{}');
                }

                return $this->redirect([
                    'controller' => 'BookingRules',
                    'action' => 'view',
                    $entry->rule_id,
                ]);
            } else {
                $this->Flash->error(__d('expenses', 'The account entry could not be saved. Please, try again.'));
            }
        }

        // Pre-fill rule_id from query string when creating a new entry
        $ruleId = $this->getRequest()->getQuery('rule_id');
        if ($entry->isNew() && $ruleId) {
            $entry->rule_id = $ruleId;
        }

        $this->set(compact('entry'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id BookingRuleAccountEntry id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);

        $entry = $this->BookingRuleAccountEntries->get($id, contain: ['BookingRules']);
        $this->Authorization->authorize($entry);

        $ruleId = $entry->rule_id;

        if ($this->BookingRuleAccountEntries->delete($entry)) {
            $this->Flash->success(__d('expenses', 'The account entry has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The account entry could not be deleted. Please, try again.'));
        }

        return $this->redirect([
            'controller' => 'BookingRules',
            'action' => 'view',
            $ruleId,
        ]);
    }
}
