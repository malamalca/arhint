<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Expenses\Model\Entity\BookingRuleFilter;

/**
 * BookingRuleFilters Model
 *
 * @property \Expenses\Model\Table\BookingRulesTable $BookingRules
 * @method \Expenses\Model\Entity\BookingRuleFilter get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\BookingRuleFilter newEmptyEntity()
 * @method \Expenses\Model\Entity\BookingRuleFilter patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @extends \Cake\ORM\Table<array{}, \Expenses\Model\Entity\BookingRuleFilter>
 */
class BookingRuleFiltersTable extends Table
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

        $this->setTable('booking_rule_filters');
        $this->setDisplayField('field');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('BookingRules', [
            'foreignKey' => 'rule_id',
            'className' => 'Expenses.BookingRules',
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
            ->uuid('rule_id')
            ->notEmptyString('rule_id');

        $validator
            ->integer('left_bracket_count')
            ->greaterThanOrEqual('left_bracket_count', 0)
            ->notEmptyString('left_bracket_count');

        $validator
            ->notEmptyString('field')
            ->maxLength('field', 100);

        $validator
            ->notEmptyString('operator')
            ->inList('operator', BookingRuleFilter::OPERATORS);

        $validator
            ->allowEmptyString('value')
            ->maxLength('value', 255);

        $validator
            ->integer('right_bracket_count')
            ->greaterThanOrEqual('right_bracket_count', 0)
            ->notEmptyString('right_bracket_count');

        $validator
            ->allowEmptyString('end_operator')
            ->inList('end_operator', BookingRuleFilter::END_OPERATORS);

        $validator
            ->integer('sort')
            ->notEmptyString('sort');

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
        $rules->add($rules->existsIn('rule_id', 'BookingRules'));

        return $rules;
    }

    /**
     * Returns the next sort value for a given rule.
     *
     * @param string $ruleId BookingRule id.
     * @return int
     */
    public function nextSort(string $ruleId): int
    {
        $max = $this->find()
            ->where(['rule_id' => $ruleId])
            ->select(['max_sort' => $this->find()->func()->max('sort')])
            ->disableHydration()
            ->first();

        return (int)($max['max_sort'] ?? 0) + 10;
    }
}
