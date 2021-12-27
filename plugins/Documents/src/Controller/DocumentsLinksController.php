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
     * @return \Cake\Http\Response|null
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (!empty($this->Security)) {
            if (in_array($this->getRequest()->getParam('action'), ['link'])) {
                $this->Security->setConfig(
                    'unlockedFields',
                    ['document_id']
                );
            }
        }

        return null;
    }

    /**
     * Link method
     *
     * @param string $documentId Document id.
     * @return \Cake\Http\Response|null
     */
    public function link($documentId = null)
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            if ($this->DocumentsLinks->two($documentId, $this->getRequest()->getData('document_id'))) {
                $this->Flash->success(__d('documents', 'Documents have been successfully linked.'));

                return $this->redirect(['controller' => 'documents', 'action' => 'view', $documentId]);
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
        $DocumentsLink = $this->DocumentsLinks->get($id);
        $this->Authorization->skipAuthorization();

        if ($this->DocumentsLinks->delete($DocumentsLink)) {
            $this->Flash->success(__d('documents', 'The documents link has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The documents link could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'documents', 'action' => 'view', $documentId]);
    }
}
