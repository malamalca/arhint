<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use ZipArchive;

/**
 * DocumentsAttachments Controller
 *
 * @property \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachments
 */
class DocumentsAttachmentsController extends AppController
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
        $a = $this->DocumentsAttachments->get($id);

        $this->Authorization->authorize($a, 'view');

        $path = Configure::read('Documents.uploadFolder') . DS . $a->filename;

        $this->set(compact('a', 'path'));
    }

    /**
     * Download method
     *
     * @param string $id   Documents Attachment id.
     * @param bool|null $forceDownload Force download of an attachment.
     * @param string|null $name Documents Attachment name.
     * @return \Cake\Http\Response
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function download(string $id, ?bool $forceDownload = true, ?string $name = null): Response
    {
        $a = $this->DocumentsAttachments->get($id);

        $this->Authorization->authorize($a, 'view');

        $path = Configure::read('Documents.uploadFolder') . DS . $a->filename;
        $response = $this->response->withFile($path, ['name' => $a->original, 'download' => (bool)$forceDownload]);

        $finfoType = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfoType) {
            $mimeType = finfo_file($finfoType, $path);
            if ($mimeType) {
                $response = $response->withType($mimeType);
            }
        }

        return $response;
    }

    /**
     * Download all attachment for specified Document
     *
     * @param string $documentId   Document id.
     * @return \Cake\Http\Response
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function downloadAll(string $documentId): Response
    {
        $attachments = $this->Authorization->applyScope($this->DocumentsAttachments->find(), 'index')
            ->where(['document_id' => $documentId])
            ->all();

        if ($attachments->count() == 0) {
            throw new NotFoundException(__d('documents', 'No Attachments Found.'));
        }

        if ($attachments->count() == 1) {
            $path = Configure::read('Documents.uploadFolder') . DS . $attachments->first()->filename;
            $this->response = $this->response->withFile(
                $path,
                ['name' => $attachments->first()->original, 'download' => true],
            );
        } else {
            $tmpFilename = uniqid('attachments') . '.zip';

            $zip = new ZipArchive();
            $zip->open(constant('TMP') . $tmpFilename, ZipArchive::CREATE);
            foreach ($attachments as $attachment) {
                $path = Configure::read('Documents.uploadFolder') . DS . $attachment->filename;
                $zip->addFile($path, $attachment->original);
            }
            $zip->close();
            $this->response = $this->response->withFile(
                constant('TMP') . $tmpFilename,
                ['name' => $tmpFilename, 'download' => true],
            );
            unlink(constant('TMP') . $tmpFilename);
        }

        return $this->response;
    }

    /**
     * Add method
     *
     * @param string $model Documents model (Document, Invoice, TravelOrder)
     * @param string $documentId Documents id.
     * @param string $mode Mode - 'upload' or 'scan'
     * @return \Cake\Http\Response|null
     */
    public function add(string $model, string $documentId, string $mode = 'upload'): ?Response
    {
        $uploadDir = Configure::read('Documents.uploadFolder');
        if (!is_writable($uploadDir)) {
            if (Configure::read('debug')) {
                die(sprintf('Target Folder %s is not writeable.', $uploadDir));
            } else {
                die('Target Folder is not writeable.');
            }
        }

        if ($mode == 'scan') {
            $this->viewBuilder()->setTemplate('scan');
        }

        $attachment = $this->DocumentsAttachments->newEmptyEntity();
        $attachment->document_id = $documentId;
        $attachment->model = $model;

        $this->Authorization->authorize($attachment, 'edit');

        if ($this->getRequest()->is('post')) {
            $tmpNames = [];
            $data = $this->getRequest()->getData();
            if ($mode == 'scan') {
                // process scanned image
                $pdfData = $this->getRequest()->getData('scanned');
                if (!empty($pdfData)) {
                    $tmpName = uniqid('scan') . '.pdf';
                    $tmpNames[$tmpName] = tempnam(constant('TMP'), 'LilScan') . '.pdf';
                    $tmpBinary = base64_decode($pdfData);
                    file_put_contents($tmpName, $tmpBinary);

                    $data['filename'] = [
                        'name' => $tmpName,
                        'type' => 'application/pdf',
                        'size' => strlen($tmpBinary),
                    ];
                    unset($data['scanned']);
                }
            } else {
                $uploadedFile = $this->getRequest()->getData('filename');
                if (!empty($uploadedFile) && !$uploadedFile->getError()) {
                    $tmpNames[$uploadedFile->getClientFilename()] = $uploadedFile->getStream()->getMetadata('uri');
                }
            }

            $attachment = $this->DocumentsAttachments->patchEntity($attachment, $data);

            if (!$attachment->getErrors()) {
                if ($this->DocumentsAttachments->save($attachment, ['uploadedFilename' => $tmpNames])) {
                    $this->Flash->success(__d('documents', 'The documents attachment has been saved.'));

                    return $this->redirect($this->getRequest()->getData('referer') ?? ['action' => 'index']);
                } else {
                    $this->Flash->error(__d('documents', 'The attachment could not be saved. Please, try again.'));
                }
            }
        }
        $this->set(compact('attachment'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Documents Attachment id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $attachment = $this->DocumentsAttachments->get($id);

        $this->Authorization->authorize($attachment);

        if ($this->DocumentsAttachments->delete($attachment)) {
            $this->Flash->success(__d('documents', 'The documents attachment has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The attachment could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->referer() ?? ['action' => 'index']);
    }
}
