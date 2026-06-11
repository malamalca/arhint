<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Model\Entity\TravelOrdersExpense;

/**
 * TravelOrdersExpenses Model
 *
 * @property \Documents\Model\Table\TravelOrdersTable&\Cake\ORM\Association\BelongsTo $TravelOrders
 * @method \Documents\Model\Entity\TravelOrdersExpense newEmptyEntity()
 * @method \Documents\Model\Entity\TravelOrdersExpense newEntity(array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersExpense[] newEntities(array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersExpense get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\TravelOrdersExpense patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersExpense[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersExpense|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TravelOrdersExpensesTable extends Table
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

        $this->setTable('travel_orders_expenses');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('TravelOrders', [
            'foreignKey' => 'travel_order_id',
            'className' => 'Documents\Model\Table\TravelOrdersTable',
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
            ->uuid('travel_order_id')
            ->notEmptyString('travel_order_id');

        $validator
            ->dateTime('start_time')
            ->allowEmptyDateTime('start_time');

        $validator
            ->dateTime('end_time')
            ->allowEmptyDateTime('end_time');

        $validator
            ->scalar('type')
            ->maxLength('type', 100)
            ->notEmptyString('type');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->decimal('quantity')
            ->greaterThan('quantity', 0)
            ->notEmptyString('quantity');

        $validator
            ->decimal('price')
            ->greaterThanOrEqual('price', 0)
            ->notEmptyString('price');

        $validator
            ->scalar('currency')
            ->maxLength('currency', 10)
            ->notEmptyString('currency');

        $validator
            ->decimal('total')
            ->allowEmptyString('total');

        $validator
            ->decimal('approved_total')
            ->allowEmptyString('approved_total');

        return $validator;
    }

    /**
     * After save: recalculate parent travel order total.
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterSave(EventInterface $event, TravelOrdersExpense $entity, ArrayObject $options): void
    {
        $this->updateTravelOrderTotal($entity->travel_order_id);
    }

    /**
     * After delete: recalculate parent travel order total.
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterDelete(EventInterface $event, TravelOrdersExpense $entity, ArrayObject $options): void
    {
        $this->updateTravelOrderTotal($entity->travel_order_id);
    }

    /**
     * Recalculate and persist the total on a TravelOrder from its mileages + expenses.
     *
     * @param string $travelOrderId Travel order id
     * @return void
     */
    private function updateTravelOrderTotal(string $travelOrderId): void
    {
        /** @var \Documents\Model\Table\TravelOrdersMileagesTable $MileagesTable */
        $MileagesTable = TableRegistry::getTableLocator()->get('Documents.TravelOrdersMileages');
        $mileageRow = $MileagesTable->find()
            ->where(['travel_order_id' => $travelOrderId])
            ->select(['s' => $MileagesTable->find()->func()->sum('total')])
            ->disableHydration()
            ->first();
        $mileageSum = (float)($mileageRow['s'] ?? 0);

        $expenses = $this->find()
            ->where(['travel_order_id' => $travelOrderId])
            ->select(['total', 'approved_total'])
            ->disableHydration()
            ->all();
        $expenseSum = 0;
        foreach ($expenses as $expense) {
            $expenseSum += !empty($expense['approved_total'])
                ? (float)$expense['approved_total']
                : (float)($expense['total'] ?? 0);
        }

        /** @var \Documents\Model\Table\TravelOrdersTable $TravelOrdersTable */
        $TravelOrdersTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
        $travelOrder = $TravelOrdersTable->get($travelOrderId);
        $travelOrder->total = round($mileageSum + $expenseSum, 2);
        $TravelOrdersTable->save($travelOrder);
    }
}
