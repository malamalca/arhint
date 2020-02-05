<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

use Cake\ORM\TableRegistry;

/**
 * InvoicesCounters Controller
 *
 * @property \LilInvoices\Model\Table\InvoicesCountersTable $InvoicesCounters
 */
class InvoicesCountersController extends AppController
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
            ['conditions' => [
                'InvoicesCounters.active IN' => [true],
            ]],
            $this->InvoicesCounters->filter($filter)
        );

        $counters = $this->Authorization->applyScope($this->InvoicesCounters->find())
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->all();

        $this->set(compact('counters', 'filter'));

        return null;
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null
     */
    public function add()
    {
        $this->setAction('edit');

        return null;
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoices Counter id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if (empty($id)) {
            $counter = $this->InvoicesCounters->newEmptyEntity();
            $counter->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $counter = $this->InvoicesCounters->get($id);
        }

        $this->Authorization->authorize($counter);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $counter = $this->InvoicesCounters->patchEntity($counter, $this->getRequest()->getData());

            if (!$counter->getErrors()) {
                if ($this->InvoicesCounters->save($counter)) {
                    $this->Flash->success(__d('lil_invoices', 'The invoices counter has been saved.'));

                    return $this->redirect(['action' => 'index']);
                }
            }
            $this->Flash->error(__d('lil_invoices', 'The invoices counter could not be saved. Please, try again.'));
        }

        /** @var \LilInvoices\Model\Table\InvoicesTemplatesTable $InvoicesTemplates */
        $InvoicesTemplates = TableRegistry::getTableLocator()->get('LilInvoices.InvoicesTemplates');
        $templates = $InvoicesTemplates->findForOwner($this->getCurrentUser()->get('company_id'));
        $this->set(compact('counter', 'templates'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoices Counter id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $invoicesCounter = $this->InvoicesCounters->get($id);
        $this->Authorization->authorize($invoicesCounter);

        if ($this->InvoicesCounters->delete($invoicesCounter)) {
            $this->Flash->success(__d('lil_invoices', 'The invoices counter has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_invoices', 'The invoices counter could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
