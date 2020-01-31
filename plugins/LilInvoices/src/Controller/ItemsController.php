<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

use Cake\Http\Exception\NotFoundException;

/**
 * Items Controller
 *
 * @property \LilInvoices\Model\Table\ItemsTable $Items
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
     * Add method
     *
     * @return void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $this->setAction('edit');
    }

    /**
     * Edit method
     *
     * @param string|null $id Item id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
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
                $this->Flash->success(__d('lil_invoices', 'The item has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('lil_invoices', 'The item could not be saved. Please, try again.'));
            }
        }

        $vats = $this->Items->Vats->find('list', [
                'keyField' => 'id',
                'valueField' => 'descript',
            ])
            ->where(['owner_id' => $this->getCurrentUser()->get('company_id')])
            ->order(['descript'])
            ->toArray();

        $this->set(compact('item', 'vats'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Item id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $item = $this->Items->get($id);
        $this->Authorization->authorize($item);

        if ($this->Items->delete($item)) {
            $this->Flash->success(__d('lil_invoices', 'The item has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_invoices', 'The item could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * autocomplete method
     *
     * @return \Cake\Http\Response|null
     */
    public function autocomplete()
    {
        if ($this->getRequest()->is('ajax')) {
            $items = [];
            $term = $this->getRequest()->getQuery('term');
            if ($term) {
                $items = $this->Authorization->applyScope($this->Items->find(), 'index')
                    ->select($this->Items)
                    ->select(['value' => 'Items.descript', 'label' => 'Items.descript'])
                    ->select($this->Items->Vats)
                    ->contain(['Vats'])
                    ->where(['Items.descript LIKE' => '%' . $term . '%'])
                    ->order('Items.descript')
                    ->all();
            }

            $response = $this->getResponse()
                ->withType('application/json')
                ->withStringBody(json_encode($items));

            return $response;
        } else {
            throw new NotFoundException(__d('lil_invoices', 'Invalid ajax call.'));
        }
    }
}
