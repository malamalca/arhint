<?php
declare(strict_types=1);

namespace Calendar\Controller;

/**
 * CalendarsController
 */
class CalendarsController extends AppController
{
    /**
     * View method
     *
     * @param string|null $id Event id.
     * @return \Cake\Http\Response|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $calendarId = $this->getCurrentUser()->get('id');

        /** @var \Calendar\Model\Table\EventsTable $EventsTable */
        $EventsTable = $this->fetchTable('Calendar.Events');

        $filter = $this->getRequest()->getQueryParams();
        $params = array_merge_recursive([
            'contain' => [],
            'conditions' => ['calendar_id' => $calendarId],
            'order' => ['Events.dat_start ASC'],
        ], $EventsTable->filter($filter));

        $events = $this->Authorization->applyScope($EventsTable->find(), 'index')
            ->select()
            ->where($params['conditions'])
            ->contain($params['contain'])
            ->order($params['order'])
            ->all();

        $this->set(compact('events'));
    }
}
