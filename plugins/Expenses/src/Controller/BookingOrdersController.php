<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Expenses\Filter\BookingOrdersFilter;
use Expenses\Lib\BookingLinker;
use Expenses\Model\Entity\BookingOrder;

/**
 * BookingOrders Controller
 *
 * @property \Expenses\Model\Table\BookingOrdersTable $BookingOrders
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BookingOrdersController extends AppController
{
    /**
     * BeforeFilter event handler
     *
     * @param \Cake\Event\EventInterface $event Event interface
     * @return void
     */
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);

        if (in_array($this->getRequest()->getParam('action'), ['links'])) {
            $this->FormProtection->setConfig(
                'unlockedFields',
                ['entries'],
            );
        }
    }

    /**
     * Index method – list booking orders with optional filter.
     *
     * @return void
     */
    public function index(): void
    {
        $ownerId = $this->getCurrentUser()->get('company_id');

        $docFilter = new BookingOrdersFilter($this->getRequest()->getQuery('q', ''));

        $filter = ['owner_id' => $ownerId];
        if ($docFilter->get('status') !== null) {
            $filter['status'] = (string)$docFilter->get('status');
        }
        if ($docFilter->get('opener') !== null) {
            $filter['opener'] = (string)$docFilter->get('opener');
        }
        if ($docFilter->get('sort') !== null) {
            $filter['sort'] = (string)$docFilter->get('sort');
        }
        if ($docFilter->get('span') !== null) {
            $filter['span'] = (string)$docFilter->get('span');
        }
        $terms = $docFilter->getFields()['terms'] ?? [];
        if (!empty($terms)) {
            $filter['search'] = implode(' ', $terms);
        }

        $params = $this->BookingOrders->filter($filter, $ownerId);

        $query = $this->Authorization->applyScope($this->BookingOrders->find())
            ->select(['id', 'no', 'title', 'date_created', 'status', 'opener_id'])
            ->contain(array_merge([
                'Openers' => function ($q) {
                    return $q->select(['id', 'name']);
                },
                'BookingOrderEntries',
            ], $params['contain']))
            ->where($params['conditions'])
            ->orderBy($params['order']);

        $data = $this->paginate($query);

        // status counts (unfiltered by status/opener for accurate badges)
        $baseConditions = ['BookingOrders.owner_id' => $ownerId];
        $statusCounts = $this->BookingOrders->statusCounts($baseConditions);
        $openCount = 0;
        $closedCount = 0;
        foreach ($statusCounts as $status => $count) {
            if ($status === BookingOrder::STATUS_LOCKED) {
                $closedCount += $count;
            } else {
                $openCount += $count;
            }
        }

        // users for opener dropdown (admin only)
        $users = [];
        if ($this->getCurrentUser()->hasRole('admin')) {
            /** @var \App\Model\Table\UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
            $users = $UsersTable->fetchForCompany($ownerId);
        }

        $statusList = $this->BookingOrders->statusList();

        $this->set(compact('data', 'docFilter', 'statusCounts', 'openCount', 'closedCount', 'users', 'statusList'));
    }

    /**
     * View method – display a booking order with its entries.
     *
     * @param string|null $id BookingOrder id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $bookingOrder = $this->BookingOrders->get(
            $id,
            contain: [
                'Openers',
                'BookingOrderEntries' => ['Accounts', 'Partners' => ['Contacts']],
            ],
        );

        $this->Authorization->authorize($bookingOrder);

        $this->set(compact('bookingOrder'));
    }

    /**
     * Edit method – add or edit a booking order.
     *
     * @param string|null $id BookingOrder id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $bookingOrder = $this->BookingOrders->get(
                $id,
                contain: ['BookingOrderEntries'],
            );
        } else {
            $bookingOrder = $this->BookingOrders->newEmptyEntity();
        }

        $this->Authorization->authorize($bookingOrder);

        $statusList = $this->BookingOrders->statusList();

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if ($bookingOrder->isNew()) {
                $data['owner_id'] = $this->getCurrentUser()->get('company_id');
                $data['opener_id'] = $this->getCurrentUser()->get('id');
                if (empty($data['status'])) {
                    $data['status'] = 'draft';
                }
            }

            $bookingOrder = $this->BookingOrders->patchEntity($bookingOrder, $data);

            if ($this->BookingOrders->save($bookingOrder)) {
                if ($this->getRequest()->is('ajax')) {
                    $value = $bookingOrder->no . ' – ' . $bookingOrder->title;

                    return $this->getResponse()
                        ->withType('application/json')
                        ->withStringBody((string)json_encode([
                            'id' => $bookingOrder->id,
                            'value' => $value,
                        ]));
                }

                $this->Flash->success(__d('expenses', 'The booking order has been saved.'));

                return $this->redirect(['action' => 'view', $bookingOrder->id]);
            } else {
                $this->Flash->error(__d('expenses', 'The booking order could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('bookingOrder', 'statusList'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id BookingOrder id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $bookingOrder = $this->BookingOrders->get($id);
        $this->Authorization->authorize($bookingOrder);

        if ($this->BookingOrders->delete($bookingOrder)) {
            $this->Flash->success(__d('expenses', 'The booking order has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The booking order could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Post method – moves a booking order from draft to posted status.
     *
     * @param string|null $id BookingOrder id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function post(?string $id = null): ?Response
    {
        $bookingOrder = $this->BookingOrders->get($id);
        $this->Authorization->authorize($bookingOrder);

        $bookingOrder->status = 'posted';

        if ($this->BookingOrders->save($bookingOrder)) {
            $this->Flash->success(__d('expenses', 'The booking order has been posted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The booking order could not be posted.'));
        }

        return $this->redirect(['action' => 'view', $bookingOrder->id]);
    }

    /**
     * Lock method – moves a booking order from posted to locked status.
     *
     * @param string|null $id BookingOrder id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function lock(?string $id = null): ?Response
    {
        $bookingOrder = $this->BookingOrders->get($id);
        $this->Authorization->authorize($bookingOrder);

        $bookingOrder->status = 'locked';

        if ($this->BookingOrders->save($bookingOrder)) {
            $this->Flash->success(__d('expenses', 'The booking order has been locked.'));
        } else {
            $this->Flash->error(__d('expenses', 'The booking order could not be locked.'));
        }

        return $this->redirect(['action' => 'view', $bookingOrder->id]);
    }

    /**
     * Links action – view and manage BookingOrderEntries for any linkable entity.
     *
     * Accepts query params:
     *   model     – entity class name from the whitelist (e.g. 'BankStatementEntry')
     *   foreignid – primary key (UUID) of the source entity
     *   bookingid – (optional) UUID of a draft BookingOrder to link to, or 'new'
     *               to create a new one (with bo_no / bo_title / bo_date params).
     *               When absent the first step (order selector) is shown instead.
     *
     * GET without bookingid:  entity info + existing entries + BookingOrder picker (step 1).
     * GET with bookingid:     entity info + existing entries + editable entry rows (step 2).
     * POST:                   saves submitted entries; 'bookingid' must be in the POST body.
     *                         When bookingid=new the order is created first.
     *
     * @return \Cake\Http\Response|null
     */
    public function links(): ?Response
    {
        $model = (string)$this->getRequest()->getQuery('model', '');
        $foreignId = (string)$this->getRequest()->getQuery('foreignid', '');

        $linker = new BookingLinker();
        if (!$linker->isSupportedModel($model) || empty($foreignId)) {
            throw new BadRequestException();
        }

        // Authorization is handled manually (cross-plugin entities).
        $this->Authorization->skipAuthorization();

        [$sourceTable, $contain] = $linker->resolveSourceTable($model);
        if ($sourceTable === null) {
            throw new BadRequestException();
        }
        $entity = $sourceTable->get($foreignId, contain: $contain);

        $linker->assertOwnership($entity, $model, $this->getCurrentUser()->get('company_id'));

        /** @var \Expenses\Model\Table\BookingOrderEntriesTable $boeTable */
        $boeTable = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
        $existing = $boeTable->entriesForEntity($model, $foreignId);

        // this is the first booking order linked to the entity (if any) – used for pre-selecting the order in the UI.
        // all booking order entries shoud belong to the same order, so it's enough to check the first one (if any)
        // for the initial booking order label in the UI)
        $initialBookingOrder = $existing->first()?->booking_order;
        if (!$initialBookingOrder) {
            $initialBookingOrderId = (string)$this->getRequest()->getQuery(
                'bookingid',
                $this->getRequest()->getData('bookingid', ''),
            );
            if (!empty($initialBookingOrderId)) {
                $initialBookingOrder = $this->BookingOrders->get($initialBookingOrderId);
                $this->Authorization->authorize($initialBookingOrder, 'edit');
            }
        }

        $initialPartner = $existing->first()?->partner;
        if (!$initialPartner) {
            $initialPartnerId = (string)$this->getRequest()->getQuery(
                'partnerid',
                $this->getRequest()->getData('partner_id', ''),
            );
            if (!empty($initialPartnerId)) {
                $initialPartner = TableRegistry::getTableLocator()->get('Expenses.Partners')->get($initialPartnerId);
            }
        }

        $isLocked = !empty($initialBookingOrder) && $initialBookingOrder->status !== BookingOrder::STATUS_DRAFT;

        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            if (!$initialBookingOrder) {
                $this->Flash->error(__d('expenses', 'Please select or create a booking order first.'));

                return $this->redirect(['action' => 'links', '?' => [
                    'model' => $model, 'foreignid' => $foreignId,
                ]]);
            }
            if (!$initialPartner) {
                $this->Flash->error(__d('expenses', 'Please select a partner first.'));

                return $this->redirect(['action' => 'links', '?' => [
                    'model' => $model, 'foreignid' => $foreignId, 'bookingid' => $initialBookingOrder->id,
                ]]);
            }

            // If the order is not in draft status, prevent changes to existing entries (only allow adding new ones).
            $entriesData = (array)$this->getRequest()->getData('entries', []);
            $entriesToSave = [];
            $newEntryNos = [];
            foreach ($entriesData as $entry) {
                $entry = array_merge($entry, [
                    'model' => $model,
                    'foreign_id' => $foreignId,
                    'descript' => $linker->entityDescript($entity, $model),
                    'partner_id' => $initialPartner->id,
                    'booking_order_id' => $initialBookingOrder->id,
                ]);
                if ($entry['id']) {
                    $entriesToSave[] = $boeTable->patchEntity($existing->firstMatch(['id' => $entry['id']]), $entry);
                } else {
                    $entriesToSave[] = $boeTable->newEntity($entry);
                    $newEntryNos[] = (int)$entry['no'];
                }
            }

            // Delete removed entries (those present in DB but not in the submitted data).
            $existingIds = $existing->extract('id')->toArray();
            $keepIds = array_filter(array_column($entriesData, 'id'));
            $entriesToDelete = array_diff($existingIds, $keepIds);

            $connection = $boeTable->getConnection();
            $saved = $connection->transactional(
                function () use (
                    $boeTable,
                    $initialBookingOrder,
                    $entriesToDelete,
                    $newEntryNos,
                    $keepIds,
                    $entriesToSave,
                ) {
                    if (!empty($entriesToDelete)) {
                        $boeTable->deleteAll(['id IN' => $entriesToDelete]);
                    }

                    if (!empty($newEntryNos) && min($newEntryNos) > 0) {
                        $boeTable->shiftPositions(
                            $initialBookingOrder->id,
                            min($newEntryNos),
                            count($newEntryNos),
                            array_values($keepIds),
                        );
                    }

                    if (!$boeTable->saveMany($entriesToSave)) {
                        return false;
                    }

                    $boeTable->renumberPositions($initialBookingOrder->id);

                    return true;
                },
            );

            if ($saved) {
                $this->Flash->success(__d('expenses', 'Bookings saved.'));

                return $this->redirect(
                    $this->getRequest()->getData('redirect') ?: ['action' => 'view', $initialBookingOrder->id],
                );
            } else {
                $this->Flash->error(__d('expenses', 'Some booking entries could not be saved.'));
            }
        }

        $entityInfo = $linker->entityInfo($entity, $model);

        // When there are no existing entries for this entity, new rows should start after the
        // last position already occupied in the selected booking order.
        // Use 0 when no booking order is selected yet – the JS will update via updateNoForNewRows().
        $nextNo = 0;
        if ($existing->isEmpty() && $initialBookingOrder) {
            $nextNo = $boeTable->maxNoForOrder($initialBookingOrder->id) + 1;
        }

        // Build the editable entries data for the view.  This will be used to prefill the form fields in table
        $displayEntries = $linker->buildDisplayEntries(
            $existing,
            $isLocked,
            $entity,
            $model,
            $this->getCurrentUser()->get('company_id'),
            $nextNo,
        );

        // Resolve the initial booking order label
        $initialBoId = $initialBookingOrder ? $initialBookingOrder->id : '';
        $initialBoLabel = $initialBookingOrder ? (string)$initialBookingOrder : '';
        $initialPartnerId = $initialPartner ? $initialPartner->id : '';
        $initalPartnerLabel = $initialPartner && $initialPartner->contact ? (string)$initialPartner->contact : '';

        $this->set(compact(
            'entity',
            'model',
            'foreignId',
            'entityInfo',
            'isLocked',
            'displayEntries',
            'initialBoId',
            'initialBoLabel',
            'initialPartnerId',
            'initalPartnerLabel',
        ));

        return null;
    }

    /**
     * Returns the next available position (max no + 1) for entries in a booking order.
     * Used by the links form to pre-fill `no` for newly proposed rows.
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When not an AJAX call.
     */
    public function nextPosition(): void
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $this->Authorization->skipAuthorization();
            $bookingOrderId = (string)$this->getRequest()->getQuery('bookingorderid', '');
            $nextNo = 1;
            if (!empty($bookingOrderId)) {
                /** @var \Expenses\Model\Table\BookingOrderEntriesTable $boeTable */
                $boeTable = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
                $nextNo = $boeTable->maxNoForOrder($bookingOrderId) + 1;
            }
            $this->set(compact('nextNo'));
        } else {
            throw new NotFoundException();
        }
    }

    /**
     * Autocomplete method – search draft booking orders by number or title.
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When not an AJAX call.
     */
    public function autocomplete(): void
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $this->Authorization->skipAuthorization();
            $term = $this->getRequest()->getQuery('term');
            $bookingOrders = [];
            if (is_string($term) && $term !== '') {
                $bookingOrders = $this->BookingOrders->find()
                    ->where([
                        'owner_id' => (string)$this->getCurrentUser()->get('company_id'),
                        'status' => BookingOrder::STATUS_DRAFT,
                        'OR' => [
                            'no LIKE' => '%' . $term . '%',
                            'title LIKE' => '%' . $term . '%',
                        ],
                    ])
                    ->select(['id', 'no', 'title'])
                    ->orderBy(['date_created' => 'DESC', 'no' => 'ASC'])
                    ->limit(20)
                    ->all();
            }
            $this->set(compact('bookingOrders'));
        } else {
            throw new NotFoundException();
        }
    }
}
