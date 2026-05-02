<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Partners Model
 *
 * @property \Crm\Model\Table\ContactsTable $Contacts
 * @property \Expenses\Model\Table\BookingOrderEntriesTable $BookingOrderEntries
 * @method \Expenses\Model\Entity\Partner get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\Partner newEmptyEntity()
 * @method \Expenses\Model\Entity\Partner patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class PartnersTable extends Table
{
    /** @var array<string> Allowed role values */
    public const ROLES = ['buyer', 'seller'];

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('partners');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Contacts', [
            'foreignKey' => 'contact_id',
            'className' => 'Crm.Contacts',
        ]);

        $this->hasMany('BookingOrderEntries', [
            'className' => 'Expenses.BookingOrderEntries',
            'foreignKey' => 'partner_id',
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
            ->uuid('contact_id')
            ->notEmptyString('contact_id');

        $validator
            ->notEmptyString('role')
            ->inList('role', self::ROLES);

        $validator
            ->date('date_start')
            ->allowEmptyDate('date_start');

        $validator
            ->date('date_end')
            ->allowEmptyDate('date_end');

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
        $rules->add($rules->existsIn('contact_id', 'Contacts'));

        return $rules;
    }

    /**
     * Returns a key/value list of roles suitable for a select box.
     *
     * @return array<string, string>
     */
    public function roleList(): array
    {
        return [
            'buyer' => __d('expenses', 'Buyer'),
            'seller' => __d('expenses', 'Seller'),
        ];
    }

    /**
     * Filters partners by query string.
     *
     * @param array<string, mixed> $filter Filter parameters.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
    {
        $conditions = [];
        $contain = ['Contacts'];

        if (!empty($filter['search'])) {
            $search = '%' . trim($filter['search']) . '%';
            $conditions['Contacts.name LIKE'] = $search;
        }

        if (!empty($filter['contact_id'])) {
            $conditions['Partners.contact_id'] = $filter['contact_id'];
        }

        if (!empty($filter['role'])) {
            $conditions['Partners.role'] = $filter['role'];
        }

        return compact('conditions', 'contain');
    }
}
