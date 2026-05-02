<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Core\Configure;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

/**
 * BookingOrderEntries Controller
 *
 * @property \Expenses\Model\Table\BookingOrderEntriesTable $BookingOrderEntries
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BookingOrderEntriesController extends AppController
{
    /**
     * Index method – list entries for a given booking order.
     *
     * @param string|null $bookingOrderId Booking order id filter (optional).
     * @return void
     */
    public function index(?string $bookingOrderId = null): void
    {
        $query = $this->Authorization->applyScope($this->BookingOrderEntries->find())
            ->contain(['BookingOrders', 'Accounts', 'Partners' => ['Contacts']])
            ->orderBy(['BookingOrderEntries.booking_order_id' => 'ASC', 'BookingOrderEntries.no' => 'ASC']);

        if ($bookingOrderId !== null) {
            $query->where(['BookingOrderEntries.booking_order_id' => $bookingOrderId]);
        }

        $bookingOrderEntries = $query->all();

        $this->set(compact('bookingOrderEntries', 'bookingOrderId'));
    }

    /**
     * Edit method – add or edit a booking order entry.
     *
     * @param string|null $id BookingOrderEntry id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $bookingOrderEntry = $this->BookingOrderEntries->get(
                $id,
                contain: ['BookingOrders', 'Accounts', 'Partners' => ['Contacts']],
            );
        } else {
            $bookingOrderEntry = $this->BookingOrderEntries->newEmptyEntity();
        }

        $this->Authorization->authorize($bookingOrderEntry);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            if ($bookingOrderEntry->isNew() && !empty($data['booking_order_id'])) {
                $data['no'] = $this->BookingOrderEntries->nextNumber($data['booking_order_id']);
            }

            if (isset($data['partner_id']) && $data['partner_id'] === '') {
                $data['partner_id'] = null;
            }

            if (isset($data['model']) && $data['model'] === '') {
                $data['model'] = null;
            }

            if (isset($data['foreign_id']) && $data['foreign_id'] === '') {
                $data['foreign_id'] = null;
            }

            $bookingOrderEntry = $this->BookingOrderEntries->patchEntity($bookingOrderEntry, $data);

            if ($this->BookingOrderEntries->save($bookingOrderEntry)) {
                $this->Flash->success(__d('expenses', 'The entry has been saved.'));

                if ($this->getRequest()->is('ajax')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode(['success' => true]) ?: '{}');
                }

                return $this->redirect([
                    'controller' => 'BookingOrders',
                    'action' => 'view',
                    $bookingOrderEntry->booking_order_id,
                ]);
            } else {
                $this->Flash->error(__d('expenses', 'The entry could not be saved. Please, try again.'));
            }
        }

        // Pre-fill booking_order_id from query string when creating a new entry
        $bookingOrderId = $this->getRequest()->getQuery('booking_order_id');
        if ($bookingOrderEntry->isNew() && $bookingOrderId) {
            $bookingOrderEntry->booking_order_id = $bookingOrderId;
        }

        // Resolve display title for the linked document (for autocomplete pre-population)
        $linkedTitle = '';
        if (!$bookingOrderEntry->isNew() && $bookingOrderEntry->model && $bookingOrderEntry->foreign_id) {
            $tableMap = [
                'Invoices' => 'Documents.Invoices',
                'Documents' => 'Documents.Documents',
                'TravelOrders' => 'Documents.TravelOrders',
                'BankStatements' => 'Expenses.BankStatements',
            ];
            if (isset($tableMap[$bookingOrderEntry->model])) {
                $linkedDoc = TableRegistry::getTableLocator()
                    ->get($tableMap[$bookingOrderEntry->model])
                    ->find()
                    ->select(['no', 'title'])
                    ->where(['id' => $bookingOrderEntry->foreign_id])
                    ->first();
                if ($linkedDoc) {
                    $linkedTitle = $linkedDoc->no . ' – ' . $linkedDoc->title;
                }
            }
        }

        $this->set(compact('bookingOrderEntry', 'linkedTitle'));

        return null;
    }

    /**
     * Autocomplete action for linked documents (Invoices, Documents, TravelOrders).
     * Accepts ?model=X&term=Y. Only returns items whose counter is active.
     *
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When not an ajax/debug call.
     */
    public function modelAutocomplete(): void
    {
        if ($this->getRequest()->is('ajax') || Configure::read('debug')) {
            $this->Authorization->skipAuthorization();

            $modelName = $this->getRequest()->getQuery('model');
            $term = $this->getRequest()->getQuery('term');

            $allowedModels = ['Invoices', 'Documents', 'TravelOrders', 'BankStatements'];
            $tableMap = [
                'Invoices' => 'Documents.Invoices',
                'Documents' => 'Documents.Documents',
                'TravelOrders' => 'Documents.TravelOrders',
                'BankStatements' => 'Expenses.BankStatements',
            ];

            $items = [];
            if (
                is_string($modelName) && in_array($modelName, $allowedModels, true)
                && is_string($term) && $term !== ''
            ) {
                $user = $this->getCurrentUser();
                $table = TableRegistry::getTableLocator()->get($tableMap[$modelName]);

                if ($modelName === 'BankStatements') {
                    // BankStatements are scoped by owner_id; search by no or iban
                    $items = $table->find()
                        ->select(['id', 'no', 'iban', 'dat_issue'])
                        ->where([
                            'owner_id' => $user->company_id,
                            'OR' => [
                                'no LIKE' => '%' . $term . '%',
                                'iban LIKE' => '%' . $term . '%',
                            ],
                        ])
                        ->orderBy(['dat_issue' => 'DESC'])
                        ->limit(20)
                        ->all();
                } else {
                    $countersTable = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');
                    $activeCounterIds = $countersTable->find()
                        ->select(['id'])
                        ->where([
                            'active' => true,
                            'owner_id' => $user->company_id,
                        ])
                        ->all()
                        ->extract('id')
                        ->toList();

                    $items = $table->find()
                        ->select(['id', 'no', 'title'])
                        ->where([
                            'OR' => [
                                'no LIKE' => '%' . $term . '%',
                                'title LIKE' => '%' . $term . '%',
                            ],
                            'counter_id IN' => $activeCounterIds,
                        ])
                        ->orderBy(['no' => 'ASC'])
                        ->limit(20)
                        ->all();
                }
            }

            $this->set(compact('items'));
        } else {
            throw new NotFoundException(__d('expenses', 'Invalid ajax call.'));
        }
    }

    /**
     * Delete method.
     *
     * @param string|null $id BookingOrderEntry id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $bookingOrderEntry = $this->BookingOrderEntries->get(
            $id,
            contain: ['BookingOrders'],
        );
        $this->Authorization->authorize($bookingOrderEntry);

        $bookingOrderId = $bookingOrderEntry->booking_order_id;

        if ($this->BookingOrderEntries->delete($bookingOrderEntry)) {
            $this->Flash->success(__d('expenses', 'The entry has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The entry could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'BookingOrders', 'action' => 'view', $bookingOrderId]);
    }
}
