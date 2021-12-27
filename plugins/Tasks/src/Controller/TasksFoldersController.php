<?php
declare(strict_types=1);

namespace Tasks\Controller;

use Cake\Cache\Cache;

/**
 * TasksFolders Controller
 *
 * @property \Tasks\Model\Table\TasksFoldersTable $TasksFolders
 */
class TasksFoldersController extends AppController
{
    /**
     * Edit method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|void
     */
    public function edit($id = null)
    {
        if ($id) {
            $folder = $this->TasksFolders->get($id);
        } else {
            $folder = $this->TasksFolders->newEmptyEntity();
            $folder->owner_id = $this->getCurrentUser()->get('company_id');
        }

        $this->Authorization->authorize($folder);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $folder = $this->TasksFolders->patchEntity($folder, $this->getRequest()->getData());
            Cache::delete('Tasks.' . $this->getCurrentUser()->id . '.Folders');

            if ($this->TasksFolders->save($folder)) {
                $this->Flash->success(__d('tasks', 'The folder has been saved.'));

                return $this->redirect(['controller' => 'Tasks', 'action' => 'index', 'folder' => $folder->id]);
            } else {
                $this->Flash->error(__d('tasks', 'The folder could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('folder'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Task id.
     * @return \Cake\Http\Response|void
     */
    public function delete($id = null)
    {
        $tasksFolder = $this->TasksFolders->get($id);

        $this->Authorization->authorize($tasksFolder);

        if ($this->TasksFolders->delete($tasksFolder)) {
            Cache::delete('Tasks.' . $this->getCurrentUser()->id . '.Folders');

            $this->Flash->success(__d('tasks', 'The folder has been deleted.'));
        } else {
            $this->Flash->error(__d('tasks', 'The folder could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'Tasks', 'action' => 'index']);
    }
}
