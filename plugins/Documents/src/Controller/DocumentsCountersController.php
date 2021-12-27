<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

/**
 * DocumentsCounters Controller
 *
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class DocumentsCountersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();

        $params = array_merge_recursive(
            [
                'conditions' => ['DocumentsCounters.active IN' => [true]],
            ],
            $this->DocumentsCounters->filter($filter)
        );

        $query = $this->Authorization->applyScope($this->DocumentsCounters->find())
            ->where($params['conditions'])
            ->contain($params['contain']);

        $counters = $this->paginate($query, ['limit' => 10]);
        $this->set(compact('counters', 'filter'));

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Documents Counter id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if (empty($id)) {
            $counter = $this->DocumentsCounters->newEmptyEntity();
            $counter->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $counter = $this->DocumentsCounters->get($id);
        }

        $this->Authorization->authorize($counter);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $counter = $this->DocumentsCounters->patchEntity($counter, $this->getRequest()->getData());

            if (!$counter->getErrors()) {
                if ($this->DocumentsCounters->save($counter)) {
                    $this->Flash->success(__d('documents', 'The documents counter has been saved.'));

                    Cache::delete('Documents.sidebarCounters.' . $this->getCurrentUser()->id);

                    return $this->redirect(['action' => 'index']);
                }
            }
            $this->Flash->error(__d('documents', 'The documents counter could not be saved. Please, try again.'));
        }

        /** @var \Documents\Model\Table\DocumentsTemplatesTable $DocumentsTemplates */
        $DocumentsTemplates = TableRegistry::getTableLocator()->get('Documents.DocumentsTemplates');
        $templates = $DocumentsTemplates->findForOwner($this->getCurrentUser()->get('company_id'));
        $this->set(compact('counter', 'templates'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Documents Counter id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $DocumentsCounter = $this->DocumentsCounters->get($id);
        $this->Authorization->authorize($DocumentsCounter);

        if ($this->DocumentsCounters->delete($DocumentsCounter)) {
            $this->Flash->success(__d('documents', 'The documents counter has been deleted.'));

            Cache::delete('Documents.sidebarCounters.' . $this->getCurrentUser()->id);
        } else {
            $this->Flash->error(__d('documents', 'The documents counter could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
