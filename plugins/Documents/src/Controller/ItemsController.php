<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

/**
 * Items Controller
 *
 * @property \Documents\Model\Table\ItemsTable $Items
 */
class ItemsController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $items = $this->Authorization->applyScope($this->Items->find())
            ->order('Items.descript')
            ->all();
        $this->set(compact('items'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Item id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $item = $this->Items->newEmptyEntity();
            $item->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $item = $this->Items->get($id);
        }

        $this->Authorization->authorize($item);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $item = $this->Items->patchEntity($item, $this->getRequest()->getData());
            $item->owner_id = $this->getCurrentUser()->get('company_id');

            if ($this->Items->save($item)) {
                $this->Flash->success(__d('documents', 'The item has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'The item could not be saved. Please, try again.'));
            }
        }

        $vats = $this->Items->Vats->find('list', keyField: 'id', valueField: 'descript')
            ->where(['owner_id' => $this->getCurrentUser()->get('company_id')])
            ->order(['descript'])
            ->toArray();

        $this->set(compact('item', 'vats'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Item id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $item = $this->Items->get($id);
        $this->Authorization->authorize($item);

        if ($this->Items->delete($item)) {
            $this->Flash->success(__d('documents', 'The item has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * autocomplete method
     *
     * @return \Cake\Http\Response
     */
    public function autocomplete(): Response
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $items = [];
            $term = $this->getRequest()->getQuery('term');
            if ($term) {
                $items = $this->Authorization->applyScope($this->Items->find(), 'index')
                    ->select($this->Items)
                    ->select(['value' => 'Items.descript', 'text' => 'Items.descript'])
                    ->select($this->Items->Vats)
                    ->contain(['Vats'])
                    ->where(['Items.descript LIKE' => '%' . $term . '%'])
                    ->order('Items.descript')
                    ->all();
            }

            $response = $this->getResponse()
                ->withType('application/json')
                ->withStringBody((string)json_encode($items));

            return $response;
        } else {
            throw new NotFoundException(__d('documents', 'Invalid ajax call.'));
        }
    }
}
