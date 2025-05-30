<?php
declare(strict_types=1);

namespace Calendar\Controller;

use Cake\Http\Response;

/**
 * Events Controller
 *
 * @property \Calendar\Model\Table\EventsTable $Events
 * @method \Cake\Datasource\ResultSetInterface|\Cake\ORM\ResultSet paginate($object = null, array $settings = [])
 */
class EventsController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|void Renders view
     */
    public function index()
    {
        $filter = $this->getRequest()->getQueryParams();

        $calendarId = $this->getCurrentUser()->get('id');
        if ($this->getRequest()->getQuery('kind') == 'public') {
            $calendarId = $this->getCurrentUser()->get('company_id');
        }

        $params = array_merge_recursive([
            'contain' => [],
            'conditions' => ['calendar_id' => $calendarId],
            'order' => ['Events.dat_start ASC'],
        ], $this->Events->filter($filter));

        $events = $this->Authorization->applyScope($this->Events->find())
            ->select()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->orderBy($params['order'])
            ->all();

        $this->set(compact('events', 'filter'));
    }

    /**
     * View method
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $event = $this->Events->get($id);

        $this->Authorization->authorize($event);

        $this->set(compact('event'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $event = $this->Events->get($id);
        } else {
            $event = $this->Events->newEmptyEntity();
            $event->calendar_id = $this->getCurrentUser()->get('id');
        }

        $this->Authorization->authorize($event);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($this->getRequest()->getData('delete') == 'delete') {
                if ($this->Events->delete($event)) {
                    $this->Flash->success(__d('calendar', 'The event has been deleted.'));
                } else {
                    $this->Flash->error(__d('calendar', 'The event could not be deleted. Please, try again.'));
                }

                $referer = $this->getRequest()->getData('referer');

                return $this->redirect($referer ?? ['action' => 'index']);
            }

            $this->Events->patchEntity($event, $this->request->getData());
            if ($this->Events->save($event)) {
                $this->Flash->success(__d('calendar', 'The event has been saved.'));

                $referer = $this->getRequest()->getData('referer');

                return $this->redirect($referer ?? ['action' => 'index']);
            }
            $this->Flash->error(__d('calendar', 'The event could not be saved. Please, try again.'));
        }

        $this->set(compact('event'));

        return null;
    }

    /**
     * Delete method
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $event = $this->Events->get($id);
        $this->Authorization->authorize($event);

        if ($this->Events->delete($event)) {
            $this->Flash->success(__d('calendar', 'The event has been deleted.'));
        } else {
            $this->Flash->error(__d('calendar', 'The event could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
