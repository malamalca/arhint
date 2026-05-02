<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * BankStatementEntries Model
 *
 * @property \Expenses\Model\Table\BankStatementsTable $BankStatements
 * @method \Expenses\Model\Entity\BankStatementEntry get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\BankStatementEntry newEmptyEntity()
 * @method \Expenses\Model\Entity\BankStatementEntry patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class BankStatementEntriesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('bank_statement_entries');
        $this->setDisplayField('descript');
        $this->setPrimaryKey('id');

        $this->belongsTo('BankStatements', [
            'foreignKey' => 'statement_id',
            'className' => 'Expenses.BankStatements',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->uuid('statement_id')
            ->notEmptyString('statement_id');

        $validator
            ->allowEmptyString('no')
            ->maxLength('no', 50);

        $validator
            ->allowEmptyString('client')
            ->maxLength('client', 255);

        $validator
            ->allowEmptyString('descript')
            ->maxLength('descript', 500);

        $validator
            ->decimal('credit')
            ->greaterThanOrEqual('credit', 0)
            ->notEmptyString('credit');

        $validator
            ->decimal('debit')
            ->greaterThanOrEqual('debit', 0)
            ->notEmptyString('debit');

        $validator
            ->allowEmptyString('iban')
            ->maxLength('iban', 50);

        $validator
            ->allowEmptyString('ref')
            ->maxLength('ref', 255);

        $validator
            ->date('dat_issue')
            ->allowEmptyDate('dat_issue');

        return $validator;
    }

    /**
     * Returns a rules checker object.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('statement_id', 'BankStatements'));

        return $rules;
    }
}
