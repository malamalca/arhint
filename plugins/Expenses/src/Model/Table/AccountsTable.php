<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Accounts Model – Chart of Accounts (Enotni kontni načrt)
 *
 * @method \Expenses\Model\Entity\Account get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\Account newEmptyEntity()
 * @method \Expenses\Model\Entity\Account patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @mixin \Cake\ORM\Behavior\TreeBehavior
 * @extends \Cake\ORM\Table<array{}, \Expenses\Model\Entity\Account>
 */
class AccountsTable extends Table
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

        $this->setTable('accounts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
        $this->addBehavior('Tree', [
            'left' => 'lft',
            'right' => 'rght',
            'level' => 'level',
        ]);

        $this->belongsTo('ParentAccounts', [
            'className' => 'Expenses.Accounts',
            'foreignKey' => 'parent_id',
        ]);

        $this->hasMany('ChildAccounts', [
            'className' => 'Expenses.Accounts',
            'foreignKey' => 'parent_id',
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
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->notEmptyString('code')
            ->maxLength('code', 20);

        $validator
            ->notEmptyString('name')
            ->maxLength('name', 255);

        $validator
            ->integer('parent_id')
            ->allowEmptyString('parent_id');

        return $validator;
    }

    /**
     * Filters accounts by query string.
     *
     * @param array<string, mixed> $filter Filter array.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
    {
        $conditions = [];
        $contain = [];

        if (!empty($filter['search'])) {
            $search = '%' . trim($filter['search']) . '%';
            $conditions['OR'] = [
                'Accounts.code LIKE' => $search,
                'Accounts.name LIKE' => $search,
            ];
        }

        return compact('conditions', 'contain');
    }

    /**
     * Returns a flat list suitable for a select box (indented by level).
     *
     * @return array<int, string>
     */
    public function listForSelect(): array
    {
        /** @var array<int, string> $accounts */
        $accounts = $this->find('treeList', [
            'keyPath' => 'id',
            'valuePath' => 'name',
            'spacer' => '— ',
        ])->toArray();

        return $accounts;
    }
}
