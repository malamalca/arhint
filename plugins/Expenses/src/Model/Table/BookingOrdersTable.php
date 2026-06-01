<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Expenses\Model\Entity\BookingOrder;

/**
 * BookingOrders Model
 *
 * @property \App\Model\Table\UsersTable $Owners
 * @property \App\Model\Table\UsersTable $Openers
 * @property \Expenses\Model\Table\BookingOrderEntriesTable $BookingOrderEntries
 * @method \Expenses\Model\Entity\BookingOrder get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\BookingOrder newEmptyEntity()
 * @method \Expenses\Model\Entity\BookingOrder patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @extends \Cake\ORM\Table<array{}, \Expenses\Model\Entity\BookingOrder>
 */
class BookingOrdersTable extends Table
{
    /** @var array<string> Allowed status values */
    public const STATUSES = ['draft', 'posted', 'locked'];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('booking_orders');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Owners', [
            'foreignKey' => 'owner_id',
            'className' => 'App\Model\Table\UsersTable',
        ]);

        $this->belongsTo('Openers', [
            'foreignKey' => 'opener_id',
            'className' => 'App\Model\Table\UsersTable',
        ]);

        $this->hasMany('BookingOrderEntries', [
            'className' => 'Expenses.BookingOrderEntries',
            'foreignKey' => 'booking_order_id',
            'dependent' => true,
            'sort' => ['BookingOrderEntries.no' => 'ASC'],
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
            ->uuid('opener_id')
            ->notEmptyString('opener_id');

        $validator
            ->notEmptyString('no')
            ->maxLength('no', 50);

        $validator
            ->notEmptyString('title')
            ->maxLength('title', 255);

        $validator
            ->date('date_created')
            ->notEmptyDate('date_created');

        $validator
            ->notEmptyString('status')
            ->inList('status', self::STATUSES);

        $validator
            ->allowEmptyString('model')
            ->maxLength('model', 50);

        $validator
            ->allowEmptyString('foreign_id', null, function ($context) {
                return empty($context['data']['model']);
            })
            ->uuid('foreign_id');

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
        $rules->add($rules->existsIn('opener_id', 'Openers'));

        return $rules;
    }

    /**
     * Returns a key/value list of statuses suitable for a select box.
     *
     * @return array<string, string>
     */
    public function statusList(): array
    {
        return BookingOrder::statusLabels();
    }

    /**
     * Returns count of booking orders per status for the given base conditions.
     *
     * @param array<string, mixed> $baseConditions ORM where conditions.
     * @return array<string, int>
     */
    public function statusCounts(array $baseConditions): array
    {
        $rows = $this->find()
            ->select(['status', 'cnt' => $this->find()->func()->count('*')])
            ->where($baseConditions)
            ->groupBy('status')
            ->disableHydration()
            ->all()
            ->toArray();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int)$row['cnt'];
        }

        return $counts;
    }

    /**
     * Filters booking orders by query string.
     *
     * @param array<string, mixed> $filter    Filter parameters.
     * @param string               $ownerId   Owner (company) id.
     * @return array<string, mixed>
     */
    public function filter(array &$filter, string $ownerId): array
    {
        $conditions = ['BookingOrders.owner_id' => $ownerId];
        $contain = [];

        if (!empty($filter['search'])) {
            $search = '%' . trim($filter['search']) . '%';
            $conditions['OR'] = [
                'BookingOrders.no LIKE' => $search,
                'BookingOrders.title LIKE' => $search,
            ];
        }

        if (!empty($filter['status'])) {
            $closedStatuses = [BookingOrder::STATUS_LOCKED];
            if ($filter['status'] === 'open') {
                $conditions['BookingOrders.status NOT IN'] = $closedStatuses;
            } elseif ($filter['status'] === 'closed') {
                $conditions['BookingOrders.status IN'] = $closedStatuses;
            } else {
                $conditions['BookingOrders.status'] = $filter['status'];
            }
        }

        if (!empty($filter['opener'])) {
            $UsersTable = TableRegistry::getTableLocator()->get('App.Users');
            $matchingUsers = $UsersTable->find()
                ->select(['id'])
                ->where(['name LIKE' => '%' . $filter['opener'] . '%']);
            $conditions['BookingOrders.opener_id IN'] = $matchingUsers;
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
                $conditions['BookingOrders.date_created >='] = $start;
                $conditions['BookingOrders.date_created <='] = $end;
            }
        }

        $order = ['BookingOrders.date_created' => 'DESC', 'BookingOrders.no' => 'DESC'];
        if (!empty($filter['sort'])) {
            $order = match ($filter['sort']) {
                'oldest' => ['BookingOrders.date_created' => 'ASC', 'BookingOrders.no' => 'ASC'],
                'date-desc' => ['BookingOrders.date_created' => 'DESC'],
                'date-asc' => ['BookingOrders.date_created' => 'ASC'],
                'opener' => ['Openers.name' => 'ASC'],
                default => ['BookingOrders.date_created' => 'DESC', 'BookingOrders.no' => 'DESC'],
            };
        }

        return compact('conditions', 'contain', 'order');
    }
}
