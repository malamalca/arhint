<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Expenses Model
 *
 * @property \Expenses\Model\Table\PaymentsTable $Payments
 * @property \Documents\Model\Table\InvoicesTable $Invoices
 * @method \Expenses\Model\Entity\Expense get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\Expense newEmptyEntity()
 * @method \Expenses\Model\Entity\Expense patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class ExpensesTable extends Table
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

        $this->setTable('expenses');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsToMany('Payments', [
            'foreignKey' => 'expense_id',
            'targetForeignKey' => 'payment_id',
            'joinTable' => 'payments_expenses',
            'className' => 'Expenses.Payments',
        ]);

        $this->belongsTo('Invoices', [
            'foreignKey' => 'foreign_id',
            'className' => 'Documents.Invoices',
            'conditions' => ['Expenses.model' => 'Invoice'],
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
            ->allowEmptyString('id', 'create');

        $validator
            ->allowEmptyString('model');

        $validator
            ->add('dat_happened', 'valid', ['rule' => 'date'])
            ->allowEmptyString('dat_happened');

        $validator
            ->allowEmptyString('title');

        $validator
            ->add('net_total', 'valid', ['rule' => 'decimal'])
            ->allowEmptyString('net_total');

        $validator
            ->add('total', 'valid', ['rule' => 'decimal'])
            ->allowEmptyString('total');

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
        return $rules;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Expenses\Model\Entity\Expense $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return bool
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options): bool
    {
        if ($entity->isNew() && !empty($entity->dat_happened) && empty($entity->month)) {
            $entity->month = (string)$entity->dat_happened->i18nFormat('yyyy-MM');
        }

        return true;
    }

    /**
     * Filters accounts by query string
     *
     * @param array<string, mixed> $filter Filter array.
     * @param string $ownerId Owner Company Id.
     * @return array<string, mixed>
     */
    public function filter(array &$filter, string $ownerId): array
    {
        $ret = ['conditions' => [], 'order' => []];

        if (isset($filter['span'])) {
            if ($filter['span'] == 'month') {
                $startMonth = '01';
                $endMonth = '12';
                if (isset($filter['month']) && $filter['month'] >= 1 && $filter['month'] <= 12) {
                    $startMonth = $endMonth = str_pad($filter['month'], 2, '0', STR_PAD_LEFT);
                }

                if (!isset($filter['year'])) {
                    $filter['year'] = date('Y');
                }

                $start = implode('-', [$filter['year'], $startMonth, '01']);
                $end = implode('-', [
                    $filter['year'],
                    $endMonth,
                    date('t', (int)strtotime(implode('-', [$filter['year'], $endMonth, '01']))),
                ]);

                $ret['conditions'][] = function ($exp) use ($start, $end) {
                    return $exp->between('Expenses.dat_happened', $start, $end, 'date');
                };
            }
            if ($filter['span'] == 'fromto') {
                $start = DateTime::parseDateTime($filter['start'], 'yyyy-MM-dd');
                if (!isset($filter['start']) || empty($start)) {
                    $start = DateTime::parseDateTime(date('Y') . '-01-01', 'yyyy-MM-dd');
                }
                $filter['start'] = $ret['conditions']['Expenses.dat_happened >='] = $start?->i18nFormat('yyyy-MM-dd');

                $end = DateTime::parseDateTime($filter['end'], 'yyyy-MM-dd');
                if (!isset($filter['end']) || empty($end)) {
                    $end = DateTime::now();
                }
                $filter['end'] = $ret['conditions']['Expenses.dat_happened <='] = $end->i18nFormat('yyyy-MM-dd');
            }
        }

        if (isset($filter['type'])) {
            if ($filter['type'] == 'income') {
                $ret['conditions']['Expenses.total >='] = 0;
            }
            if ($filter['type'] == 'expenses') {
                $ret['conditions']['Expenses.total <'] = 0;
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
    public function isOwnedBy(string $entityId, string $ownerId): bool
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * Returns minYear for expense with specified query params.
     * Returns current year if no expenses found.
     *
     * @param string $ownerId Company Id.
     * @return string
     */
    public function minYear(string $ownerId): string
    {
        $q = $this->find();

        /** @var \Expenses\Model\Entity\Expense $r */
        $r = $q->select(['min_date' => $q->func()
            ->min('dat_happened', ['string'])])
            ->where(['owner_id' => $ownerId])
            ->first();

        if (!empty($r->min_date)) {
            return substr($r->min_date, 0, 4);
        }

        return date('Y');
    }

    /**
     * Returns monthly totals
     *
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @param array<string, mixed> $options Options array
     * @return array<\Expenses\Model\Entity\Expense>
     */
    public function monthlyTotals(SelectQuery $query, array $options): array
    {
        $year = $options['year'] ?? '2020';

        $query = $query
            ->select(['Expenses.month', 'monthly_amount' => $query->func()->sum('Expenses.total')])
            ->where(['Expenses.month LIKE' => $year . '-%'])->group('Expenses.month');

        if (isset($options['kind']) && $options['kind'] == 'expenses') {
            $query = $query->andWhere(['Expenses.total <' => 0]);
        } else {
            $query = $query->andWhere(['Expenses.total >' => 0]);
        }

        $data = $query
            ->all()
            ->combine('month', 'monthly_amount')
            ->toArray();

        if (!empty($options['cummulative'])) {
            $prev = 0;
            foreach ($data as $month => $total) {
                $data[$month] = abs($total) + $prev;
                $prev = $data[$month];
            }
        }

        return $data;
    }
}
