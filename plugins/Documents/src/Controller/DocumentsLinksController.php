<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Event\EventInterface;

/**
 * DocumentsLinks Controller
 *
 * @property \Documents\Model\Table\DocumentsLinksTable $DocumentsLinks
 */
class DocumentsLinksController extends AppController
{
    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return \Cake\Http\Response|void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!empty($this->Security)) {
            if (in_array($this->getRequest()->getParam('action'), ['link'])) {
                $this->Security->setConfig(
                    'unlockedFields',
                    ['document_id', 'model']
                );
            }
        }
    }

    /**
     * Link method
     *
     * @param string $model Document model
     * @param string $documentId Document id.
     * @return \Cake\Http\Response|null
     */
    public function link($model, $documentId = null)
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            if (
                (bool)$this->DocumentsLinks->two(
                    $model,
                    $documentId,
                    $this->getRequest()->getData('model'),
                    $this->getRequest()->getData('document_id')
                )
            ) {
                $this->Flash->success(__d('documents', 'Documents have been successfully linked.'));

                $referer = $this->getRequest()->getData('referer');

                return $this->redirect(!empty($referer) ? $referer : ['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'The link could not be established. Please, try again.'));
            }
        }

        return null;
    }

    /**
     * Delete method
     *
     * @param string $documentId Documents id.
     * @param string $id Documents Link id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete($documentId, $id)
    {
        $documentsLink = $this->DocumentsLinks->get($id);
        $this->Authorization->skipAuthorization();

        if ($this->DocumentsLinks->delete($documentsLink)) {
            $this->Flash->success(__d('documents', 'The documents link has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The documents link could not be deleted. Please, try again.'));
        }

        return $this->redirect($this->getRequest()->referer() ?? ['action' => 'index']);
    }
}
