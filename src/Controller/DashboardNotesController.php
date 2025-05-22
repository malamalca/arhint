<?php
declare(strict_types=1);

namespace App\Controller;

/**
 * DashboardNotes Controller
 *
 * @property \App\Model\Table\DashboardNotesTable $DashboardNotes
 * @method \Cake\Datasource\Paging\PaginatedInterface paginate($object = null, array $settings = [])
 */
class DashboardNotesController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Item id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null)
    {
        if (empty($id)) {
            $note = $this->DashboardNotes->newEmptyEntity();
            $note->user_id = $this->getCurrentUser()->get('id');
        } else {
            $note = $this->DashboardNotes->get($id);
        }

        $this->Authorization->authorize($note);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $note = $this->DashboardNotes->patchEntity($note, $this->getRequest()->getData());

            if ($this->DashboardNotes->save($note)) {
                $this->Flash->success(__('The note has been saved.'));

                return $this->redirect(
                    $this->getRequest()->getData('referer', ['controller' => 'Pages', 'action' => 'dashboard']),
                );
            } else {
                $this->Flash->error(__('The note could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('note'));
    }
}
