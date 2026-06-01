<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * BankStatements Model
 *
 * @property \App\Model\Table\UsersTable $Owners
 * @property \App\Model\Table\UsersTable $Users
 * @property \Expenses\Model\Table\BankStatementEntriesTable $BankStatementEntries
 * @method \Expenses\Model\Entity\BankStatement get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\BankStatement newEmptyEntity()
 * @method \Expenses\Model\Entity\BankStatement patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @extends \Cake\ORM\Table<array{}, \Expenses\Model\Entity\BankStatement>
 */
class BankStatementsTable extends Table
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

        $this->setTable('bank_statements');
        $this->setDisplayField('no');
        $this->setPrimaryKey('id');

        $this->belongsTo('Owners', [
            'foreignKey' => 'owner_id',
            'className' => 'App\Model\Table\UsersTable',
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'App\Model\Table\UsersTable',
        ]);

        $this->hasMany('BankStatementEntries', [
            'className' => 'Expenses.BankStatementEntries',
            'foreignKey' => 'statement_id',
            'dependent' => true,
            'sort' => ['BankStatementEntries.dat_issue' => 'ASC'],
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
            ->uuid('owner_id')
            ->notEmptyString('owner_id');

        $validator
            ->uuid('user_id')
            ->notEmptyString('user_id');

        $validator
            ->notEmptyString('no')
            ->maxLength('no', 100);

        $validator
            ->integer('seq_no')
            ->greaterThanOrEqual('seq_no', 0)
            ->allowEmptyString('seq_no');

        $validator
            ->allowEmptyString('kind')
            ->maxLength('kind', 50);

        $validator
            ->notEmptyString('iban')
            ->maxLength('iban', 50);

        $validator
            ->date('dat_issue')
            ->notEmptyDate('dat_issue');

        $validator
            ->notEmptyString('currency')
            ->maxLength('currency', 10);

        $validator
            ->dateTime('dat_import')
            ->notEmptyDateTime('dat_import');

        $validator
            ->decimal('total_credit')
            ->greaterThanOrEqual('total_credit', 0)
            ->notEmptyString('total_credit');

        $validator
            ->decimal('total_debit')
            ->greaterThanOrEqual('total_debit', 0)
            ->notEmptyString('total_debit');

        $validator
            ->integer('count_credit')
            ->greaterThanOrEqual('count_credit', 0)
            ->notEmptyString('count_credit');

        $validator
            ->integer('count_debit')
            ->greaterThanOrEqual('count_debit', 0)
            ->notEmptyString('count_debit');

        $validator
            ->decimal('saldo')
            ->notEmptyString('saldo');

        $validator
            ->decimal('balance')
            ->allowEmptyString('balance');

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
        $rules->add($rules->existsIn('user_id', 'Users'));
        $rules->add(
            $rules->isUnique(
                ['owner_id', 'no'],
                __d('expenses', 'This bank statement has already been imported.'),
            ),
        );

        return $rules;
    }

    /**
     * Returns filtered query parameters for the index.
     *
     * @param array<string, mixed> $filter    Filter parameters.
     * @param string               $ownerId   Owner (company) id.
     * @return array<string, mixed>
     */
    public function filter(array &$filter, string $ownerId): array
    {
        $conditions = ['BankStatements.owner_id' => $ownerId];
        $contain = [];

        if (!empty($filter['search'])) {
            $search = '%' . trim($filter['search']) . '%';
            $conditions['OR'] = [
                'BankStatements.no LIKE' => $search,
                'BankStatements.iban LIKE' => $search,
            ];
        }

        if (!empty($filter['iban'])) {
            $conditions['BankStatements.iban'] = $filter['iban'];
        }

        if (!empty($filter['span'])) {
            $today = Date::now();
            switch ($filter['span']) {
                case 'this-month':
                    $start = $today->startOfMonth();
                    $end = $today->endOfMonth();
                    break;
                case 'prev-month':
                    $start = $today->subMonths(1)->startOfMonth();
                    $end = $today->subMonths(1)->endOfMonth();
                    break;
                case 'last-3-months':
                    $start = $today->subMonths(3)->startOfMonth();
                    $end = $today->endOfMonth();
                    break;
                case 'this-year':
                    $start = $today->startOfYear();
                    $end = $today->endOfYear();
                    break;
                default:
                    $start = null;
                    $end = null;
            }
            if (!empty($start) && !empty($end)) {
                $conditions['BankStatements.dat_issue >='] = $start;
                $conditions['BankStatements.dat_issue <='] = $end;
            }
        }

        $order = ['BankStatements.dat_issue' => 'DESC', 'BankStatements.no' => 'DESC'];
        if (!empty($filter['sort'])) {
            $order = match ($filter['sort']) {
                'date-asc' => ['BankStatements.dat_issue' => 'ASC', 'BankStatements.no' => 'ASC'],
                'date-desc' => ['BankStatements.dat_issue' => 'DESC', 'BankStatements.no' => 'DESC'],
                default => ['BankStatements.dat_issue' => 'DESC', 'BankStatements.no' => 'DESC'],
            };
        }

        return compact('conditions', 'contain', 'order');
    }

    /**
     * Returns a distinct list of IBANs for the given owner.
     *
     * @param string $ownerId Owner id.
     * @return array<string>
     */
    public function ibanList(string $ownerId): array
    {
        return $this->find()
            ->select(['iban'])
            ->where(['owner_id' => $ownerId])
            ->groupBy('iban')
            ->orderBy(['iban' => 'ASC'])
            ->disableHydration()
            ->all()
            ->extract('iban')
            ->toList();
    }
}
