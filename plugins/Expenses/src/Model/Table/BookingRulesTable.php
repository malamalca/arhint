<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use Expenses\Model\Entity\BookingRule;

/**
 * BookingRules Model
 *
 * @property \App\Model\Table\UsersTable $Owners
 * @property \Expenses\Model\Table\BookingRuleFiltersTable $BookingRuleFilters
 * @property \Expenses\Model\Table\BookingRuleAccountEntriesTable $BookingRuleAccountEntries
 * @method \Expenses\Model\Entity\BookingRule get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\BookingRule newEmptyEntity()
 * @method \Expenses\Model\Entity\BookingRule patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class BookingRulesTable extends Table
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

        $this->setTable('booking_rules');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Owners', [
            'foreignKey' => 'owner_id',
            'className' => 'App\Model\Table\UsersTable',
        ]);

        $this->hasMany('BookingRuleFilters', [
            'className' => 'Expenses.BookingRuleFilters',
            'foreignKey' => 'rule_id',
            'dependent' => true,
            'sort' => ['BookingRuleFilters.sort' => 'ASC'],
        ]);

        $this->hasMany('BookingRuleAccountEntries', [
            'className' => 'Expenses.BookingRuleAccountEntries',
            'foreignKey' => 'rule_id',
            'dependent' => true,
            'sort' => ['BookingRuleAccountEntries.sort' => 'ASC'],
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
            ->notEmptyString('model')
            ->inList('model', BookingRule::MODELS);

        $validator
            ->notEmptyString('title')
            ->maxLength('title', 255);

        return $validator;
    }

    /**
     * Returns a key/value list of models suitable for a select box.
     *
     * @return array<string, string>
     */
    public function modelList(): array
    {
        return BookingRule::modelLabels();
    }
}
