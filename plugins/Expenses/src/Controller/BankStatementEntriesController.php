<?php
declare(strict_types=1);

namespace Expenses\Controller;

use Cake\Http\Response;

/**
 * BankStatementEntries Controller
 *
 * @property \Expenses\Model\Table\BankStatementEntriesTable $BankStatementEntries
 * @method \App\Model\Entity\User getCurrentUser()
 */
class BankStatementEntriesController extends AppController
{
    /**
     * Index method – list entries for a given bank statement.
     *
     * @param string|null $statementId Bank statement id filter (optional).
     * @return void
     */
    public function index(?string $statementId = null): void
    {
        $query = $this->Authorization->applyScope($this->BankStatementEntries->find())
            ->contain(['BankStatements'])
            ->orderBy(['BankStatementEntries.dat_issue' => 'ASC']);

        if ($statementId !== null) {
            $query->where(['BankStatementEntries.statement_id' => $statementId]);
        }

        $bankStatementEntries = $query->all();

        $this->set(compact('bankStatementEntries', 'statementId'));
    }

    /**
     * Edit method – edit a bank statement entry.
     *
     * @param string|null $id BankStatementEntry id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function edit(?string $id = null): ?Response
    {
        if ($id) {
            $bankStatementEntry = $this->BankStatementEntries->get(
                $id,
                contain: ['BankStatements'],
            );
        } else {
            $bankStatementEntry = $this->BankStatementEntries->newEmptyEntity();
        }

        $this->Authorization->authorize($bankStatementEntry);

        if ($this->getRequest()->is(['patch', 'post', 'put'])) {
            $data = $this->getRequest()->getData();

            $bankStatementEntry = $this->BankStatementEntries->patchEntity($bankStatementEntry, $data);

            if ($this->BankStatementEntries->save($bankStatementEntry)) {
                $this->Flash->success(__d('expenses', 'The entry has been saved.'));

                if ($this->getRequest()->is('ajax')) {
                    return $this->response
                        ->withType('application/json')
                        ->withStringBody(json_encode(['success' => true]) ?: '{}');
                }

                return $this->redirect([
                    'controller' => 'BankStatements',
                    'action' => 'view',
                    $bankStatementEntry->statement_id,
                ]);
            } else {
                $this->Flash->error(__d('expenses', 'The entry could not be saved. Please, try again.'));
            }
        }

        // Pre-fill statement_id from query string when creating a new entry
        $statementId = $this->getRequest()->getQuery('statement_id');
        if ($bankStatementEntry->isNew() && $statementId) {
            $bankStatementEntry->statement_id = $statementId;
        }

        $this->set(compact('bankStatementEntry'));

        return null;
    }

    /**
     * Delete method.
     *
     * @param string|null $id BankStatementEntry id.
     * @return \Cake\Http\Response|null
     * @throws \Cake\Http\Exception\NotFoundException When record not found.
     */
    public function delete(?string $id = null): ?Response
    {
        $bankStatementEntry = $this->BankStatementEntries->get(
            $id,
            contain: ['BankStatements'],
        );
        $this->Authorization->authorize($bankStatementEntry);

        $statementId = $bankStatementEntry->statement_id;

        if ($this->BankStatementEntries->delete($bankStatementEntry)) {
            $this->Flash->success(__d('expenses', 'The entry has been deleted.'));
        } else {
            $this->Flash->error(__d('expenses', 'The entry could not be deleted. Please, try again.'));
        }

        return $this->redirect(['controller' => 'BankStatements', 'action' => 'view', $statementId]);
    }
}
