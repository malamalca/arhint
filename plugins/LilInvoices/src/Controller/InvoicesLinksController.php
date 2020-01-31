<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

/**
 * InvoicesLinks Controller
 *
 * @property \LilInvoices\Model\Table\InvoicesLinksTable $InvoicesLinks
 */
class InvoicesLinksController extends AppController
{
    /**
     * Link method
     *
     * @param string $invoiceId Invoice id.
     * @return \Cake\Http\Response|null
     */
    public function link($invoiceId = null)
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            if ($this->InvoicesLinks->two($invoiceId, $this->getRequest()->getData('invoice_id'))) {
                $this->Flash->success(__d('lil_invoices', 'Invoices have been successfully linked.'));

                return $this->redirect(['controller' => 'invoices', 'action' => 'view', $invoiceId]);
            }
        }

        return null;
    }

    /**
     * Delete method
     *
     * @param string $invoiceId Invoices id.
     * @param string $id Invoices Link id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($invoiceId, $id)
    {
        $invoicesLink = $this->InvoicesLinks->get($id);
        $this->Authorization->skipAuthorization();

        if ($this->InvoicesLinks->delete($invoicesLink)) {
            $this->Flash->success(__d('lil_invoices', 'The invoices link has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_invoices', 'The invoices link could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'invoices', 'action' => 'view', $invoiceId]);
    }
}
