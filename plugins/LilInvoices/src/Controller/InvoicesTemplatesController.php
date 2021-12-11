<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

/**
 * InvoicesTemplates Controller
 *
 * @property \LilInvoices\Model\Table\InvoicesTemplatesTable $InvoicesTemplates
 */
class InvoicesTemplatesController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $templates = $this->Authorization->applyScope($this->InvoicesTemplates->find())
            ->order('title')
            ->all();
        $this->set(compact('templates'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Invoices Template id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if (empty($id)) {
            $template = $this->InvoicesTemplates->newEmptyEntity();
            $template->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $template = $this->InvoicesTemplates->get($id);
        }

        $this->Authorization->authorize($template);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $template = $this->InvoicesTemplates->patchEntity($template, $this->getRequest()->getData());

            $attachment = $this->getRequest()->getData('body_file');
            if (!empty($attachment['tmp_name']) && file_exists($attachment['tmp_name'])) {
                $ext = strtolower(pathinfo($attachment['name'], PATHINFO_EXTENSION));
                $template->body = 'data:image/' . $ext . ';base64,' .
                    base64_encode(file_get_contents($attachment['tmp_name']));
            }

            if ($this->InvoicesTemplates->save($template)) {
                $this->Flash->success(__d('lil_invoices', 'The invoices template has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('lil_invoices', 'The template could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('template'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Invoices Template id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->getRequest()->allowMethod(['get', 'delete']);

        $template = $this->InvoicesTemplates->get($id);
        $this->Authorization->authorize($template);

        if ($this->InvoicesTemplates->delete($template)) {
            $this->Flash->success(__d('lil_invoices', 'The invoices template has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_invoices', 'The invoices template could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
