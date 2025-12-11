<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use mishahawthorn\OCRmyPDF\OCRmyPDF;

/**
 * Attachments Controller
 *
 * @property \App\Model\Table\AttachmentsTable $Attachments
 */
class AttachmentsController extends AppController
{
    /**
     * View method
     *
     * @param string $id Documents Attachment id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(string $id)
    {
        $attachment = $this->Attachments->get($id);

        $this->Authorization->authorize($attachment, 'view');

        $this->set(compact('attachment'));
    }

    /**
     * Preview method
     *
     * @param string $id Documents Attachment id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function preview(string $id)
    {
        $attachment = $this->Attachments->get($id);

        $this->Authorization->authorize($attachment, 'view');

        $this->set(compact('attachment'));
    }

    /**
     * Download method
     *
     * @param string|null $id Attachment id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function download(?string $id = null, ?bool $forceDownload = true)
    {
        $attachment = $this->Attachments->get($id, contain: []);

        $this->Authorization->authorize($attachment);

        $response = $this->response->withFile(
            $attachment->getFilePath(),
            ['name' => $attachment->filename, 'download' => (bool)$forceDownload],
        );

        $finfoType = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfoType) {
            $mimeType = finfo_file($finfoType, $attachment->getFilePath());
            if ($mimeType) {
                $response = $response->withType($mimeType);
            }
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
            if (!$attachment->model || !$attachment->foreign_id) {
                throw new NotFoundException(__('Invalid attachment model or foreign ID.'));
            }
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

                $redirect = $this->getRequest()->getData('referer', ['action' => 'index']);

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

    /**
     * ocr method
     *
     * @param string|null $id Attachment id.
     * @return \Cake\Http\Response|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function ocr(?string $id = null)
    {
        $attachment = $this->Attachments->get($id);

        $this->Authorization->authorize($attachment, 'edit');

        putenv('PATH=' . getenv('PATH') . ';' . dirname(Configure::read('Ghostscript.executable')));

        $ocr = OCRmyPDF::make($attachment->getFilePath());
        $ocr->setExecutable(Configure::read('OCRMyPDF.executable'));
        foreach (Configure::read('OCRMyPDF.options') as $option) {
            $parts = explode(' ', $option, 2);
            $ocr->setParam($parts[0], $parts[1] ?? null);
        }

        $newTmpPfdFilename = $ocr->run();

        if (!file_exists($newTmpPfdFilename) || !is_file($newTmpPfdFilename) || !filesize($newTmpPfdFilename)) {
            $this->Flash->error(__('OCR processing failed. Please, try again.'));

            $redirect = $this->getRequest()->getQuery('redirect', ['action' => 'index']);

            return $this->redirect($redirect);
        }

        rename($newTmpPfdFilename, $attachment->getFilePath());
    }
}
