<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Cache\Cache;
use Cake\ORM\TableRegistry;

/**
 * TravelOrders Controller
 *
 * @property \Documents\Model\Table\TravelOrdersTable $TravelOrders
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 * @method \Documents\Model\Entity\TravelOrder[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class TravelOrdersController extends AppController
{
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $filter = (array)$this->getRequest()->getQuery();

        $this->loadModel('Documents.DocumentsCounters');
        if (!empty($filter['counter'])) {
            $counter = $this->DocumentsCounters->get($filter['counter']);
        } else {
            $counter = $this->DocumentsCounters->findDefaultCounter(
                $this->Authorization->applyScope($this->DocumentsCounters->find(), 'index'),
                $this->getRequest()->getQuery('kind')
            );
            if (!$counter) {
                $this->Authorization->skipAuthorization();
                $this->Flash->error(__d('documents', 'No counters found. Please activate or add a new one.'));

                return $this->redirect(['controller' => 'DocumentsCounters']);
            }
        }

        $this->Authorization->authorize($counter, 'view');

        // fetch documents
        $filter['counter'] = $counter->id;
        $filter['order'] = 'TravelOrders.counter DESC';
        $params = $this->TravelOrders->filter($filter);

        $query = $this->Authorization->applyScope($this->TravelOrders->find())
            ->select(['id', 'no', 'counter', 'dat_order', 'location', 'total', 'attachments_count'])
            ->where($params['conditions'])
            ->order($params['order']);

        $data = $this->paginate($query);

        $dateSpan = $this->TravelOrders->maxSpan($filter['counter']);

        $counters = [];
        $controller = $this;
        $counters = Cache::remember(
            'Documents.sidebarCounters.' . $this->getCurrentUser()->id,
            function () use ($controller) {
                $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

                return $controller->Authorization->applyScope($DocumentsCounters->find(), 'index')
                    ->where(['active' => true])
                    ->order(['active', 'kind DESC', 'title'])
                    ->all();
            }
        );

        $this->set(compact('data', 'filter', 'counter', 'dateSpan', 'counters'));

        return null;
    }

    /**
     * View method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $travelOrder = $this->TravelOrders->get($id, [
            'contain' => [],
        ]);

        $this->set(compact('travelOrder'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null)
    {
        if ($id) {
            $travelOrder = $this->TravelOrders->get($id, [
                'contain' => ['DocumentsCounters', 'Payers'],
            ]);
        } else {
            // new entity
            $travelOrder = $this->TravelOrders->newEmptyEntity();
            $travelOrder->owner_id = $this->getCurrentUser()->get('company_id');

            $counterId = $this->getRequest()->getQuery('counter');
            if (empty($counterId)) {
                $counterId = $this->getRequest()->getData('counter_id');
            }

            /** @var \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters */
            $DocumentsCounters = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

            $travelOrder->documents_counter = $DocumentsCounters->get($counterId);
            $travelOrder->counter_id = $travelOrder->documents_counter->id;

            $travelOrder->no = $DocumentsCounters->generateNo($travelOrder->counter_id);
        }

        $this->Authorization->authorize($travelOrder);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $travelOrder = $this->TravelOrders->patchEntity($travelOrder, $this->request->getData());
            if ($this->TravelOrders->save($travelOrder)) {
                $this->Flash->success(__('The travel order has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The travel order could not be saved. Please, try again.'));
        }

        $counter = $travelOrder->documents_counter;

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $this->set(compact('travelOrder', 'counter', 'users'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|null|void Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete', 'get']);
        $travelOrder = $this->TravelOrders->get($id);
        if ($this->TravelOrders->delete($travelOrder)) {
            $this->Flash->success(__('The travel order has been deleted.'));
        } else {
            $this->Flash->error(__('The travel order could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
