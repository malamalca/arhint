<?php
declare(strict_types=1);

namespace Documents\Controller;

use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Documents\Filter\TravelOrdersFilter;
use Documents\Model\Entity\TravelOrder;

/**
 * TravelOrders Controller
 *
 * @property \Documents\Model\Table\TravelOrdersTable $TravelOrders
 * @property \Documents\Model\Table\DocumentsCountersTable $DocumentsCounters
 */
class TravelOrdersController extends BaseDocumentsController
{
    /**
     * @var string $documentsScope
     */
    public string $documentsScope = 'TravelOrders';

    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // process and sign use dynamic JS-added rows not tracked by FormProtection
        if (in_array($this->getRequest()->getParam('action'), ['process', 'sign'])) {
            $this->FormProtection->setConfig('validate', false);
        }
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void Renders view
     */
    public function index()
    {
        /** @var \Documents\Model\Entity\DocumentsCounter|\Cake\Http\Response $counter */
        $counter = parent::index();

        if ($counter instanceof Response) {
            return $counter;
        }

        $docFilter = new TravelOrdersFilter($this->getRequest()->getQuery('q', ''));

        // build filter params from query state
        $filter['counter'] = $counter->id;
        $filter['order'] = 'TravelOrders.counter DESC';
        if ($docFilter->get('status') !== null) {
            $filter['status'] = (string)$docFilter->get('status');
        }
        if ($docFilter->get('employee') !== null) {
            $filter['employee'] = (string)$docFilter->get('employee');
        }
        if ($docFilter->get('sort') !== null) {
            $filter['sort'] = (string)$docFilter->get('sort');
        }
        $terms = $docFilter->getFields()['terms'] ?? [];
        if (!empty($terms)) {
            $filter['search'] = implode(' ', $terms);
        }

        $params = $this->TravelOrders->filter($filter);

        $query = $this->Authorization->applyScope($this->TravelOrders->find())
            ->select([
                'id', 'no', 'dat_task', 'title', 'descript',
                'total', 'departure', 'arrival', 'status', 'Employees.name', 'employee_id',
            ])
            ->contain(['Employees'])
            ->where($params['conditions'])
            ->orderBy($params['order']);

        $data = $this->paginate($query);

        $dateSpan = $this->TravelOrders->maxSpan($filter['counter']);

        // status counts (unfiltered by status/employee for accurate badges)
        $baseFilter = ['counter' => $counter->id];
        $baseParams = $this->TravelOrders->filter($baseFilter);
        $statusCounts = $this->TravelOrders->statusCounts($baseParams['conditions']);
        $openCount = 0;
        $closedCount = 0;
        $closedStatuses = [TravelOrder::STATUS_COMPLETED, TravelOrder::STATUS_DECLINED];
        foreach ($statusCounts as $s => $c) {
            if (in_array($s, $closedStatuses, true)) {
                $closedCount += $c;
            } else {
                $openCount += $c;
            }
        }

        // employees on this counter for the dropdown (admin only)
        $employees = [];
        if ($this->getCurrentUser()->hasRole('admin')) {
            /** @var \App\Model\Table\UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
            $employees = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));
        }

        $this->set(compact('data', 'dateSpan', 'docFilter', 'statusCounts', 'openCount', 'closedCount', 'employees'));
    }

    /**
     * View method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view(?string $id = null)
    {
        $containTables = [
            'DocumentsCounters', 'TravelOrdersMileages', 'TravelOrdersExpenses',
            'EnteredBy', 'ApprovedBy', 'ProcessedBy', 'Attachments',
            'TplHeaders', 'TplBodies', 'TplFooters',
        ];
        if (Plugin::isLoaded('Projects')) {
            $containTables[] = 'Projects';
        }

        parent::view($id, $containTables);
    }

    /**
     * Edit method
     *
     * @param string|null $id Travel Order id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $containTables = [];

        $document = $this->TravelOrders->parseRequest($this->getRequest(), $id);

        // for sidebar
        $this->set('currentCounter', $document->documents_counter->id);

        $projects = [];
        if (Plugin::isLoaded('Projects')) {
            /** @var \Projects\Model\Table\ProjectsTable $ProjectsTable */
            $ProjectsTable = TableRegistry::getTableLocator()->get('Projects.Projects');
            $projectsQuery = $this->Authorization->applyScope($ProjectsTable->find(), 'index');
            $projects = $ProjectsTable->findForOwner($this->getCurrentUser()->company_id, $projectsQuery);
        }

        /** @var \App\Model\Table\UsersTable $UsersTable */
        $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
        $users = $UsersTable->fetchForCompany($this->getCurrentUser()->get('company_id'));

        $this->set(compact('projects', 'users'));

        if ($document->isNew()) {
            $document->entered_by_id = $this->getCurrentUser()->get('id');
            $document->entered_at = new DateTime();
        }

        return parent::edit($document, $containTables);
    }

    /**
     * Approve method - admin confirms travel order
     *
     * @param string $id Travel Order id.
     * @return \Cake\Http\Response|null
     */
    public function approve(string $id): ?Response
    {
        $document = $this->TravelOrders->get($id, contain: [
            'DocumentsCounters', 'Employees', 'TravelOrdersMileages', 'TravelOrdersExpenses',
        ]);
        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['post', 'put'])) {
            $document->status = TravelOrder::STATUS_APPROVED;
            $document->approved_by_id = $this->getCurrentUser()->get('id');
            $document->approved_at = new DateTime();

            if ($this->TravelOrders->save($document)) {
                $this->Flash->success(__d('documents', 'Travel order has been approved.'));

                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__d('documents', 'Could not approve travel order. Please, try again.'));
        }

        $this->set(compact('document'));

        return null;
    }

    /**
     * Submit method - user submits approved travel order for processing
     *
     * @param string $id Travel Order id.
     * @return \Cake\Http\Response|null
     */
    public function submit(string $id): ?Response
    {
        $document = $this->TravelOrders->get($id);
        $this->Authorization->authorize($document);

        $document->status = TravelOrder::STATUS_WAITING_PROCESSING;

        if ($this->TravelOrders->save($document)) {
            $this->Flash->success(__d('documents', 'Travel order has been submitted for processing.'));
        } else {
            $this->Flash->error(__d('documents', 'Could not submit travel order. Please, try again.'));
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Sign method - user signs draft travel order, moves it to waiting approval
     *
     * @param string $id Travel Order id.
     * @return \Cake\Http\Response|null
     */
    public function sign(string $id): ?Response
    {
        $document = $this->TravelOrders->get($id, contain: [
            'DocumentsCounters',
            'Employees',
            'TravelOrdersMileages',
            'TravelOrdersExpenses',
        ]);
        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['post', 'put'])) {
            $document->status = TravelOrder::STATUS_WAITING_APPROVAL;

            if ($this->TravelOrders->save($document)) {
                $this->Flash->success(__d('documents', 'Travel order has been signed and submitted for approval.'));

                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__d('documents', 'Could not sign travel order. Please, try again.'));
        }

        $this->set(compact('document'));

        return null;
    }

    /**
     * Decline method - admin declines a travel order that is waiting approval
     *
     * @param string $id Travel Order id.
     * @return \Cake\Http\Response|null
     */
    public function decline(string $id): ?Response
    {
        $document = $this->TravelOrders->get($id);
        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['post', 'put'])) {
            $document->status = TravelOrder::STATUS_DECLINED;

            if ($this->TravelOrders->save($document)) {
                $this->Flash->success(__d('documents', 'Travel order has been declined.'));
            } else {
                $this->Flash->error(__d('documents', 'Could not decline travel order. Please, try again.'));
            }
        }

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Process method - admin adds mileages, expenses, approval date; sets status to completed
     *
     * @param string $id Travel Order id.
     * @return \Cake\Http\Response|null
     */
    public function process(string $id): ?Response
    {
        $document = $this->TravelOrders->get($id, contain: [
            'DocumentsCounters',
            'Employees',
            'TravelOrdersMileages',
            'TravelOrdersExpenses',
        ]);
        $this->Authorization->authorize($document);

        if ($this->getRequest()->is(['post', 'put'])) {
            $document->status = TravelOrder::STATUS_COMPLETED;
            $document->processed_by_id = $this->getCurrentUser()->get('id');
            $document->processed_at = new DateTime();

            if ($this->TravelOrders->save($document)) {
                $this->Flash->success(__d('documents', 'Travel order has been processed.'));

                return $this->redirect(['action' => 'view', $id]);
            }
            $this->Flash->error(__d('documents', 'Could not save travel order processing data. Please, try again.'));
        }

        $this->set(compact('document'));

        return null;
    }
}
