<?php
declare(strict_types=1);

namespace Documents\Controller;

/**
 * DocumentsClients Controller
 *
 * @property \Documents\Model\Table\DocumentsClientsTable $DocumentsClients
 */
class DocumentsClientsController extends AppController
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
        $client = $this->DocumentsClients->get($id);

        $this->Authorization->authorize($client);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $client = $this->DocumentsClients->patchEntity($client, $this->getRequest()->getData());

            if ($this->DocumentsClients->save($client)) {
                $this->Flash->success(__d('documents', 'The client has been saved.'));

                return $this->redirect(['controller' => 'Invoices', 'action' => 'view', $client->document_id]);
            } else {
                $this->Flash->error(__d('documents', 'The client could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('client'));
    }
}
