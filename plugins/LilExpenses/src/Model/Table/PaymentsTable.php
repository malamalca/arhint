<?php
declare(strict_types=1);

namespace LilExpenses\Model\Table;

use Cake\I18n\FrozenTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Payments Model
 *
 * @property \LilExpenses\Model\Table\AccountsTable|\Cake\ORM\Association\BelongsTo $Accounts
 * @property \LilExpenses\Model\Table\ExpensesTable|\Cake\ORM\Association\BelongsToMany $Expenses
 * @property \LilExpenses\Model\Table\PaymentsAccountsTable|\Cake\ORM\Association\BelongsTo $PaymentsAccounts
 * @method \LilExpenses\Model\Entity\Payment get($primaryKey, array $options = [])
 * @method \LilExpenses\Model\Entity\Payment newEmptyEntity()
 * @method \LilExpenses\Model\Entity\Payment newEntity(array $data, array $options = [])
 */
class PaymentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payments');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('PaymentsExpenses', [
            'foreignKey' => 'payment_id',
            'className' => 'LilExpenses.PaymentsExpenses',
        ]);
        $this->belongsTo('PaymentsAccounts', [
            'foreignKey' => 'account_id',
            'className' => 'LilExpenses.PaymentsAccounts',
        ]);
        $this->belongsToMany('Expenses', [
            'foreignKey' => 'payment_id',
            'targetForeignKey' => 'expense_id',
            'joinTable' => 'payments_expenses',
            'className' => 'LilExpenses.Expenses',
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
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create');

        $validator
            ->add('dat_happened', 'valid', ['rule' => 'date'])
            ->allowEmptyString('dat_happened');

        $validator
            ->notEmptyString('descript');

        $validator
        //    ->add('amount', 'valid', ['rule' => 'numeric'])
            ->notEmptyString('amount');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['account_id'], 'PaymentsAccounts'));

        return $rules;
    }

    /**
     * Filters accounts by query string
     *
     * @param array $filter Filter array.
     * @param string $ownerId Owner Company Id.
     * @return array
     */
    public function filter(&$filter, $ownerId)
    {
        $ret = ['conditions' => [], 'order' => []];

        if (isset($filter['span'])) {
            if ($filter['span'] == 'month') {
                $startMonth = '01';
                $endMonth = '12';
                if (isset($filter['month']) && $filter['month'] >= 1 && $filter['month'] <= 12) {
                    $startMonth = $endMonth = str_pad((string)$filter['month'], 2, '0', STR_PAD_LEFT);
                }

                if (!isset($filter['year'])) {
                    $filter['year'] = date('Y');
                }

                $start = implode('-', [$filter['year'], $startMonth, '01']);
                $end = implode('-', [
                    $filter['year'],
                    $endMonth,
                    date('t', strtotime(implode('-', [$filter['year'], $endMonth, '01']))),
                ]);

                $ret['conditions'][] = function ($exp) use ($start, $end) {
                    return $exp->between('dat_happened', $start, $end, 'date');
                };
            }
            if ($filter['span'] == 'fromto') {
                $start = FrozenTime::parseDateTime($filter['start'], 'yyyy-MM-dd');
                if (!isset($filter['start']) || empty($start)) {
                    $start = FrozenTime::parseDateTime(date('Y') . '-01-01', 'yyyy-MM-dd');
                }
                $filter['start'] = $ret['conditions']['Payments.dat_happened >='] = $start->i18nFormat('yyyy-MM-dd');

                $end = FrozenTime::parseDateTime($filter['end'], 'yyyy-MM-dd');
                if (!isset($filter['end']) || empty($end)) {
                    $end = FrozenTime::now();
                }
                $filter['end'] = $ret['conditions']['Payments.dat_happened <='] = $end->i18nFormat('yyyy-MM-dd');
            }
        }

        if (empty($filter['type']) || !in_array($filter['type'], ['from', 'to'])) {
            //$filter['type'] = null;
        } else {
            if ($filter['type'] == 'from') {
                $ret['conditions']['Payments.amount <'] = 0;
            } else {
                $ret['conditions']['Payments.amount >='] = 0;
            }
        }

        if (!empty($filter['search'])) {
            $ret['conditions']['Payments.descript LIKE'] = '%' . $filter['search'] . '%';
        }

        if (!empty($filter['account'])) {
            $validAccounts = array_keys($this->PaymentsAccounts->listForOwner($ownerId));
            if (empty($filter['account']) || !in_array($filter['account'], $validAccounts)) {
                $filter['account'] = null;
            } else {
                $ret['conditions']['Payments.account_id'] = $filter['account'];
            }
        }

        return $ret;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entityId, $ownerId)
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * Returns minYear for payments with specified query params.
     * Returns current year if no payments found.
     *
     * @param string $ownerId Company Id.
     * @return string
     */
    public function minYear($ownerId)
    {
        $q = $this->find();

        /** @var \LilExpenses\Model\Entity\Payment $r */
        $r = $q->select(['min_date' => $q->func()->min('dat_happened', ['string'])])
            ->where(['owner_id' => $ownerId])
            ->first();

        if (!empty($r->min_date)) {
            return substr((string)$r->min_date, 0, 4);
        }

        return date('Y');
    }
}
