<?php
declare(strict_types=1);

namespace LilInvoices\Controller;

use Cake\Core\Configure;
use Cake\ORM\TableRegistry;

/**
 * InvoicesAttachments Controller
 *
 * @property \LilInvoices\Model\Table\InvoicesAttachmentsTable $InvoicesAttachments
 */
class InvoicesAttachmentsController extends AppController
{
    /**
     * View method
     *
     * @param  string|null $id   Invoices Attachment id.
     * @param  string|null $name Invoices Attachment name.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view($id = null, $name = null)
    {
        /** @var \LilInvoices\Model\Table\InvoicesAttachmentsTable $InvoicesAttachments */
        $InvoicesAttachments = TableRegistry::get('LilInvoices.InvoicesAttachments');
        $a = $InvoicesAttachments->get($id);

        $this->Authorization->authorize($a);

        $path = Configure::read('LilInvoices.uploadFolder') . DS . $a->filename;
        $name = $a->original;

        $this->response = $this->response->withFile($path, ['name' => $name]);

        return $this->response;
    }

    /**
     * Add method
     *
     * @param  string|null $invoiceId Invoices id.
     * @return \Cake\Http\Response|null
     */
    public function add($invoiceId = null)
    {
        $uploadDir = Configure::read('LilInvoices.uploadFolder');
        if (!is_writable($uploadDir)) {
            if (Configure::read('debug')) {
                die(sprintf('Target Folder %s is not writeable.', $uploadDir));
            } else {
                die('Target Folder is not writeable.');
            }
        }

        /** @var \LilInvoices\Model\Table\InvoicesAttachmentsTable $InvoicesAttachments */
        $InvoicesAttachments = TableRegistry::get('LilInvoices.InvoicesAttachments');

        $attachment = $InvoicesAttachments->newEmptyEntity();
        $attachment->invoice_id = $invoiceId;

        $this->Authorization->authorize($attachment, 'edit');

        if ($this->getRequest()->is('post')) {
            $tmpName = $this->getRequest()->getData('filename.tmp_name');

            $attachment = $InvoicesAttachments->patchEntity($attachment, $this->getRequest()->getData());

            if (!$attachment->getErrors()) {
                /*$attachment->original = $fileData['name'];
                $attachment->mime = $fileData['type'];
                $attachment->filesize = $fileData['size'];*/

                //$fileDest = Configure::read('LilInvoices.uploadFolder') . DS . $attachment->filename;
                //$moved = copy($tmpName, $fileDest);

                if ($InvoicesAttachments->save($attachment, ['uploadedFilename' => $tmpName])) {
                    $this->Flash->success(__d('lil_invoices', 'The invoices attachment has been saved.'));

                    return $this->redirect(['controller' => 'Invoices', 'action' => 'view', $attachment->invoice_id]);
                } else {
                    $this->Flash->error(__d('lil_invoices', 'The attachment could not be saved. Please, try again.'));
                }
            }
        }
        $this->set(compact('attachment'));

        return null;
    }

    /**
     * Delete method
     *
     * @param  string|null $id Invoices Attachment id.
     * @return mixed Redirects to index.
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($id = null)
    {
        /** @var \LilInvoices\Model\Table\InvoicesAttachmentsTable $InvoicesAttachments */
        $InvoicesAttachments = TableRegistry::get('LilInvoices.InvoicesAttachments');
        $attachment = $InvoicesAttachments->get($id);

        $this->Authorization->authorize($attachment);

        if ($InvoicesAttachments->delete($attachment)) {
            $this->Flash->success(__d('lil_invoices', 'The invoices attachment has been deleted.'));
        } else {
            $this->Flash->error(__d('lil_invoices', 'The attachment could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Invoices', 'action' => 'view', $attachment->invoice_id]);
    }
}
