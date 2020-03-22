<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

/**
 * InvoicesClients Controller
 *
 * @property \LilInvoices\Model\Table\InvoicesClientsTable $InvoicesClients
 */
class InvoicesClientsController extends AppController
{
    /**
     * Edit method
     *
     * @param string $id Item id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id)
    {
        $client = $this->InvoicesClients->get($id);

        $this->Authorization->authorize($client);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $client = $this->InvoicesClients->patchEntity($client, $this->getRequest()->getData());

            if ($this->InvoicesClients->save($client)) {
                $this->Flash->success(__d('lil_invoices', 'The client has been saved.'));

                return $this->redirect(['controller' => 'Invoices', 'action' => 'view', $client->invoice_id]);
            } else {
                $this->Flash->error(__d('lil_invoices', 'The client could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('client'));
    }
}
