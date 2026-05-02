<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;
use Expenses\Filter\BankStatementsFilter;
use Expenses\Lib\BankStatementImport;
use RuntimeException;

/**
 * BankStatements Controller
 *
 * @property \Expenses\Model\Table\BankStatementsTable $BankStatements
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BankStatementsController extends AppController
{
    /**
     * Index method – list bank statements with optional filter.
     *
     * @return void
     */
    public function index(): void
    {
        $ownerId = $this->getCurrentUser()->get('company_id');

        $docFilter = new BankStatementsFilter($this->getRequest()->getQuery('q', ''));

        $filter = ['owner_id' => $ownerId];
        if ($docFilter->get('iban') !== null) {
            $filter['iban'] = (string)$docFilter->get('iban');
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

        $params = $this->BankStatements->filter($filter, $ownerId);

        $query = $this->Authorization->applyScope($this->BankStatements->find())
            ->select(['id', 'no', 'seq_no', 'kind', 'iban', 'dat_issue', 'currency', 'dat_import',
                'total_credit', 'total_debit', 'count_credit', 'count_debit', 'saldo', 'balance', 'user_id'])
            ->contain(array_merge([
                'Users' => function ($q) {
                    return $q->select(['id', 'name']);
                },
            ], $params['contain']))
            ->where($params['conditions'])
            ->orderBy($params['order']);

        $data = $this->paginate($query);

        $ibanList = $this->BankStatements->ibanList($ownerId);

        $this->set(compact('data', 'docFilter', 'ibanList'));
    }

    /**
     * View method – display a bank statement with all entries.
     *
     * @param string|null $id BankStatement id.
     * @return void
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function view(?string $id = null): void
    {
        $bankStatement = $this->BankStatements->get(
            $id,
            contain: [
                'Users',
                'BankStatementEntries',
            ],
        );

        $this->Authorization->authorize($bankStatement);

        // Build a set of entry IDs that already have booking entries
        $entryIds = array_column($bankStatement->bank_statement_entries ?? [], 'id');
        $bookedEntryIds = [];
        if (!empty($entryIds)) {
            /** @var \Expenses\Model\Table\BookingOrderEntriesTable $boeTable */
            $boeTable = TableRegistry::getTableLocator()->get('Expenses.BookingOrderEntries');
            $bookedEntryIds = array_flip(
                $boeTable->find()
                    ->select(['foreign_id'])
                    ->where([
                        'OR' => [
                            // New model name used by BookingOrders/links action.
                            ['model' => 'BankStatementEntry', 'foreign_id IN' => $entryIds],
                            // Legacy model name from the old BankStatementEntries/bookings action.
                            ['model' => 'BankStatements', 'foreign_id IN' => $entryIds],
                        ],
                    ])
                    ->disableHydration()
                    ->all()
                    ->extract('foreign_id')
                    ->filter()
                    ->toList(),
            );
        }

        $this->set(compact('bankStatement', 'bookedEntryIds'));
    }

    /**
     * Import method – upload and parse an ISO 20022 camt.053 XML file.
     *
     * GET  → renders the upload form.
     * POST → processes the uploaded XML file and saves the statement.
     *
     * @return \Cake\Http\Response|null
     */
    public function import(): ?Response
    {
        $this->Authorization->skipAuthorization();

        if ($this->getRequest()->is(['post', 'put'])) {
            /** @var \Laminas\Diactoros\UploadedFile|null $uploadedFile */
            $uploadedFile = $this->getRequest()->getUploadedFile('xml_file');

            if ($uploadedFile === null || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
                $this->Flash->error(__d('expenses', 'Please select a valid XML file to upload.'));

                return null;
            }

            $ext = strtolower(pathinfo((string)$uploadedFile->getClientFilename(), PATHINFO_EXTENSION));
            if ($ext !== 'xml') {
                $this->Flash->error(__d('expenses', 'Only XML files are accepted.'));

                return null;
            }

            $xmlContent = (string)$uploadedFile->getStream()->getContents();

            try {
                $importer = new BankStatementImport();
                $parsed = $importer->parse($xmlContent);
            } catch (RuntimeException $e) {
                $this->Flash->error($e->getMessage());

                return null;
            }

            $user = $this->getCurrentUser();
            $statementData = array_merge($parsed['statement'], [
                'owner_id' => $user->get('company_id'),
                'user_id' => $user->get('id'),
                'dat_import' => DateTime::now(),
                'bank_statement_entries' => $parsed['entries'],
            ]);

            $bankStatement = $this->BankStatements->newEmptyEntity();
            $bankStatement = $this->BankStatements->patchEntity($bankStatement, $statementData, [
                'associated' => ['BankStatementEntries'],
            ]);

            if ($this->BankStatements->save($bankStatement, ['associated' => ['BankStatementEntries']])) {
                $this->Flash->success(__d('expenses', 'Bank statement has been imported successfully.'));

                return $this->redirect(['action' => 'view', $bankStatement->id]);
            } else {
                $errors = $bankStatement->getErrors();
                if (!empty($errors['no']['_isUnique']) || !empty($errors['owner_id']['_isUnique'])) {
                    $this->Flash->error(__d('expenses', 'This bank statement has already been imported.'));
                } else {
                    foreach ($bankStatement->bank_statement_entries ?? [] as $entry) {
                        $entryErrors = $entry->getErrors();
                        if (!empty($entryErrors)) {
                            $errors['entries'][] = $entryErrors;
                        }
                    }
                    $this->log('BankStatement save errors: ' . json_encode($errors), 'error');
                    $this->Flash->error(
                        __d('expenses', 'The statement could not be saved. Errors: {0}', json_encode($errors) ?: '[]'),
                    );
                }
            }
        }

        return null;
    }

    /**
     * Edit method.
     *
     * @param string|null $id BankStatement id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        $bankStatement = $this->BankStatements->get($id);
        $this->Authorization->authorize($bankStatement);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $bankStatement = $this->BankStatements->patchEntity(
                $bankStatement,
                $this->getRequest()->getData(),
            );

            if ($this->BankStatements->save($bankStatement)) {
                $this->Flash->success(__d('expenses', 'The bank statement has been saved.'));

                return $this->redirect(['action' => 'view', $bankStatement->id]);
            } else {
                $this->Flash->error(__d('expenses', 'The bank statement could not be saved. Please, try again.'));
            }
        }

        $this->set(compact('bankStatement'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id BankStatement id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $bankStatement = $this->BankStatements->get($id);
        $this->Authorization->authorize($bankStatement);

        if ($this->BankStatements->delete($bankStatement)) {
            $this->Flash->success(__d('expenses', 'The bank statement has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The bank statement could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
