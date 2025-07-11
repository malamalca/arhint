<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * Attachments Controller
 *
 * @property \App\Model\Table\AttachmentsTable $Attachments
 */
class AttachmentsController extends AppController
{
    /**
     * Download method
     *
     * @param string|null $id Attachment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function download(?string $id = null)
    {
        $attachment = $this->Attachments->get($id, contain: []);

        $this->Authorization->authorize($attachment);

        $response = $this->response->withFile(
            $attachment->getFilePath(),
            ['name' => $attachment->filename, 'download' => true],
        );

        $finfoType = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfoType) {
            $mimeType = finfo_file($finfoType, $attachment->getFilePath());
            if ($mimeType) {
                $response = $response->withType($mimeType);
            }
            finfo_close($finfoType);
        }

        return $response;
    }

    /**
     * Edit method
     *
     * @param string|null $id Attachment id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if ($id) {
            $attachment = $this->Attachments->get($id);
        } else {
            $attachment = $this->Attachments->newEmptyEntity();
            $attachment->model = $this->getRequest()->getQuery('model');
            $attachment->foreign_id = $this->getRequest()->getQuery('foreign_id');
        }

        $this->Authorization->authorize($attachment);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $tmpNames = [];
            $uploadedFile = $this->getRequest()->getData('filename');
            if (!empty($uploadedFile) && !$uploadedFile->getError()) {
                $tmpNames[$uploadedFile->getClientFilename()] = $uploadedFile->getStream()->getMetadata('uri');
            }

            $attachment = $this->Attachments->patchEntity($attachment, $this->request->getData());
            if ($this->Attachments->save($attachment, ['uploadedFilename' => $tmpNames])) {
                $this->Flash->success(__('The attachment has been saved.'));

                $redirect = $this->getRequest()->getData('redirect', ['action' => 'index']);

                return $this->redirect($redirect);
            }
            $this->Flash->error(__('The attachment could not be saved. Please, try again.'));
        }
        $this->set(compact('attachment'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Attachment id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $attachment = $this->Attachments->get($id);

        $this->Authorization->authorize($attachment);

        if ($this->Attachments->delete($attachment)) {
            $this->Flash->success(__('The attachment has been deleted.'));
        } else {
            $this->Flash->error(__('The attachment could not be deleted. Please, try again.'));
        }

        $redirect = $this->getRequest()->getQuery('redirect', ['action' => 'index']);

        return $this->redirect($redirect);
    }
}
