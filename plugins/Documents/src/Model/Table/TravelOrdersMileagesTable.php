<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Documents\Model\Entity\TravelOrdersMileage;

/**
 * TravelOrdersMileages Model
 *
 * @property \Documents\Model\Table\TravelOrdersTable&\Cake\ORM\Association\BelongsTo $TravelOrders
 * @method \Documents\Model\Entity\TravelOrdersMileage newEmptyEntity()
 * @method \Documents\Model\Entity\TravelOrdersMileage newEntity(array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersMileage[] newEntities(array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersMileage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\TravelOrdersMileage patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersMileage[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Documents\Model\Entity\TravelOrdersMileage|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class TravelOrdersMileagesTable extends Table
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

        $this->setTable('travel_orders_mileages');
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
            ->notEmptyDateTime('start_time');

        $validator
            ->dateTime('end_time')
            ->allowEmptyDateTime('end_time');

        $validator
            ->scalar('road_description')
            ->notEmptyString('road_description');

        $validator
            ->decimal('distance_km')
            ->greaterThan('distance_km', 0)
            ->notEmptyString('distance_km');

        $validator
            ->decimal('price_per_km')
            ->greaterThanOrEqual('price_per_km', 0)
            ->notEmptyString('price_per_km');

        $validator
            ->decimal('total')
            ->allowEmptyString('total');

        return $validator;
    }

    /**
     * After save: recalculate parent travel order total.
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Documents\Model\Entity\TravelOrdersMileage $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterSave(EventInterface $event, TravelOrdersMileage $entity, ArrayObject $options): void
    {
        $this->updateTravelOrderTotal($entity->travel_order_id);
    }

    /**
     * After delete: recalculate parent travel order total.
     *
     * @param \Cake\Event\EventInterface $event Event
     * @param \Documents\Model\Entity\TravelOrdersMileage $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterDelete(EventInterface $event, TravelOrdersMileage $entity, ArrayObject $options): void
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
        $mileageRow = $this->find()
            ->where(['travel_order_id' => $travelOrderId])
            ->select(['s' => $this->find()->func()->sum('total')])
            ->disableHydration()
            ->first();
        $mileageSum = (float)($mileageRow['s'] ?? 0);

        /** @var \Documents\Model\Table\TravelOrdersExpensesTable $ExpensesTable */
        $ExpensesTable = TableRegistry::getTableLocator()->get('Documents.TravelOrdersExpenses');
        $expenses = $ExpensesTable->find()
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
