<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Exception;

/**
 * BookingOrderEntries Model
 *
 * @property \Expenses\Model\Table\BookingOrdersTable $BookingOrders
 * @property \Expenses\Model\Table\AccountsTable $Accounts
 * @property \Expenses\Model\Table\PartnersTable $Partners
 * @method \Expenses\Model\Entity\BookingOrderEntry get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\BookingOrderEntry newEmptyEntity()
 * @method \Expenses\Model\Entity\BookingOrderEntry patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class BookingOrderEntriesTable extends Table
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

        $this->setTable('booking_order_entries');
        $this->setDisplayField('descript');
        $this->setPrimaryKey('id');

        $this->belongsTo('BookingOrders', [
            'foreignKey' => 'booking_order_id',
            'className' => 'Expenses.BookingOrders',
        ]);

        $this->belongsTo('Accounts', [
            'foreignKey' => 'account_id',
            'className' => 'Expenses.Accounts',
        ]);

        $this->belongsTo('Partners', [
            'foreignKey' => 'partner_id',
            'className' => 'Expenses.Partners',
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
            ->uuid('booking_order_id')
            ->notEmptyString('booking_order_id');

        $validator
            ->integer('account_id')
            ->notEmptyString('account_id');

        $validator
            ->uuid('partner_id')
            ->allowEmptyString('partner_id');

        $validator
            ->integer('no')
            ->notEmptyString('no');

        $validator
            ->allowEmptyString('model')
            ->maxLength('model', 50);

        $validator
            ->allowEmptyString('foreign_id', null, function ($context) {
                return empty($context['data']['model']);
            })
            ->uuid('foreign_id');

        $validator
            ->allowEmptyString('descript')
            ->maxLength('descript', 255);

        $validator
            ->decimal('debit')
            ->greaterThanOrEqual('debit', 0)
            ->allowEmptyString('debit')
            ->add('debit', 'debitOrCredit', [
                'rule' => function ($value, $context) {
                    $credit = isset($context['data']['credit']) ? (float)$context['data']['credit'] : 0.0;

                    return !((float)$value > 0 && $credit > 0);
                },
                'message' => __d('expenses', 'Only one of debit or credit can be non-zero.'),
            ]);

        $validator
            ->decimal('credit')
            ->greaterThanOrEqual('credit', 0)
            ->allowEmptyString('credit')
            ->add('credit', 'debitOrCredit', [
                'rule' => function ($value, $context) {
                    $debit = isset($context['data']['debit']) ? (float)$context['data']['debit'] : 0.0;

                    return !((float)$value > 0 && $debit > 0);
                },
                'message' => __d('expenses', 'Only one of debit or credit can be non-zero.'),
            ]);

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
        $rules->add($rules->existsIn('booking_order_id', 'BookingOrders'));
        $rules->add($rules->existsIn('account_id', 'Accounts'));
        $rules->add($rules->existsIn('partner_id', 'Partners'));

        $tableMap = [
            'Invoices' => 'Documents.Invoices',
            'Documents' => 'Documents.Documents',
            'TravelOrders' => 'Documents.TravelOrders',
        ];

        $rules->add(function ($entity, $options) use ($tableMap) {
            if (empty($entity->get('model'))) {
                return true;
            }
            if (!isset($tableMap[$entity->get('model')])) {
                return true;
            }
            try {
                $table = TableRegistry::getTableLocator()->get($tableMap[$entity->get('model')]);
                /** @var string $pk */
                $pk = $table->getPrimaryKey();

                return $table->exists([$pk => $entity->get('foreign_id')]);
            } catch (Exception $e) {
                return false;
            }
        }, 'foreignIdExists', [
            'errorField' => 'foreign_id',
            'message' => __('The foreign record does not exist.'),
        ]);

        return $rules;
    }

    /**
     * Returns existing BookingOrderEntries for the given source entity,
     * joined with Accounts, Partners, Contacts, and BookingOrders.
     *
     * @param string $model     Entity model name (e.g. 'BankStatementEntry').
     * @param string $foreignId Source entity primary key.
     * @return \Cake\Datasource\ResultSetInterface<\Expenses\Model\Entity\BookingOrderEntry>
     */
    public function entriesForEntity(string $model, string $foreignId): ResultSetInterface
    {
        return $this->find()
            ->where([
                'BookingOrderEntries.model' => $model,
                'BookingOrderEntries.foreign_id' => $foreignId,
            ])
            ->contain(['Accounts', 'Partners' => ['Contacts'], 'BookingOrders'])
            ->orderBy(['BookingOrders.no' => 'ASC', 'BookingOrderEntries.no' => 'ASC'])
            ->all();
    }

    /**
     * Returns the next available `no` for the given booking order (max + 1).
     *
     * @param string $bookingOrderId Booking order ID.
     * @return int
     */
    public function nextNumber(string $bookingOrderId): int
    {
        return $this->maxNoForOrder($bookingOrderId) + 1;
    }

    /**
     * Returns the highest `no` currently in use for the given booking order, or 0 if none.
     *
     * @param string $bookingOrderId Booking order ID.
     * @return int
     */
    public function maxNoForOrder(string $bookingOrderId): int
    {
        $result = $this->find()
            ->where(['booking_order_id' => $bookingOrderId])
            ->select(['max_no' => $this->find()->func()->max('no')])
            ->disableHydration()
            ->first();

        return (int)($result['max_no'] ?? 0);
    }

    /**
     * Shifts the `no` field of existing saved entries upward to make room for new rows
     * that are about to be inserted at positions >= $minNo in the same booking order.
     * Entries whose IDs are in $excludeIds (the already-kept rows of the current entity)
     * are not shifted.
     *
     * @param string $bookingOrderId Booking order ID.
     * @param int $minNo First position that must be vacated.
     * @param int $shiftBy How many steps to shift up.
     * @param array<string> $excludeIds Entry IDs that must not be shifted.
     * @return void
     */
    public function shiftPositions(
        string $bookingOrderId,
        int $minNo,
        int $shiftBy,
        array $excludeIds = [],
    ): void {
        $conditions = [
            'booking_order_id' => $bookingOrderId,
            'no >=' => $minNo,
        ];
        if (!empty($excludeIds)) {
            $conditions['id NOT IN'] = $excludeIds;
        }
        $this->updateAll(
            ['no' => new QueryExpression('no + ' . $shiftBy)],
            $conditions,
        );
    }

    /**
     * Renumbers positions of all entries in a booking order to a dense 1-based sequence.
     *
     * @param string $bookingOrderId Booking order ID to which the entries belong.
     * @return void
     */
    public function renumberPositions(string $bookingOrderId): void
    {
        $entries = $this->find()
            ->where(['booking_order_id' => $bookingOrderId])
            ->orderBy(['no' => 'ASC'])
            ->all();

        $position = 1;
        $entriesToSave = [];
        foreach ($entries as $entry) {
            if ((int)$entry->no !== $position) {
                $entry->no = $position;
                $entriesToSave[] = $entry;
            }
            $position++;
        }

        if (!empty($entriesToSave)) {
            // @phpstan-ignore argument.templateType
            $this->saveMany($entriesToSave);
        }
    }
}
