<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Http\Response;

/**
 * DocumentsTemplates Controller
 *
 * @property \Documents\Model\Table\DocumentsTemplatesTable $DocumentsTemplates
 */
class DocumentsTemplatesController extends AppController
{
    /**
     * Index method
     *
     * @return void
     */
    public function index()
    {
        $templates = $this->Authorization->applyScope($this->DocumentsTemplates->find())
            ->order('title')
            ->all();
        $this->set(compact('templates'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Documents Template id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if (empty($id)) {
            $template = $this->DocumentsTemplates->newEmptyEntity();
            $template->owner_id = $this->getCurrentUser()->get('company_id');
        } else {
            $template = $this->DocumentsTemplates->get($id);
        }

        $this->Authorization->authorize($template);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $template = $this->DocumentsTemplates->patchEntity($template, $this->getRequest()->getData());

            $attachment = $this->getRequest()->getData('body_file');
            if (!empty($attachment) && !$attachment->getError()) {
                $ext = strtolower(pathinfo($attachment->getClientFilename(), PATHINFO_EXTENSION));
                $template->body = 'data:image/' . $ext . ';base64,' .
                    base64_encode((string)file_get_contents($attachment->getStream()->getMetadata('uri')));
            }

            if ($this->DocumentsTemplates->save($template)) {
                $this->Flash->success(__d('documents', 'The documents template has been saved.'));

                return $this->redirect(['action' => 'index']);
            } else {
                $this->Flash->error(__d('documents', 'The template could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('template'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Documents Template id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->getRequest()->allowMethod(['get', 'delete']);

        $template = $this->DocumentsTemplates->get($id);
        $this->Authorization->authorize($template);

        if ($this->DocumentsTemplates->delete($template)) {
            $this->Flash->success(__d('documents', 'The documents template has been deleted.'));
        } else {
            $this->Flash->error(__d('documents', 'The documents template could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
