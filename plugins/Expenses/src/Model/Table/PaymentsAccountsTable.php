<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PaymentsAccounts Model
 *
 * @method \Expenses\Model\Entity\PaymentsAccount get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Expenses\Model\Entity\PaymentsAccount newEmptyEntity()
 * @method \Expenses\Model\Entity\PaymentsAccount patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class PaymentsAccountsTable extends Table
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

        $this->setTable('payments_accounts');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->notEmptyString('title');

        $validator
            ->add('primary', 'valid', ['rule' => 'boolean'])
            ->requirePresence('primary', 'create')
            ->notEmptyString('primary');

        $validator
            ->add('active', 'valid', ['rule' => 'boolean'])
            ->requirePresence('active', 'create')
            ->notEmptyString('active');

        return $validator;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Expenses\Model\Entity\PaymentsAccount $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if (!$this->exists(['owner_id' => $entity->owner_id, 'primary' => true])) {
            $entity->primary = true;
        }
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Expenses\Model\Entity\PaymentsAccount $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if ($entity->primary) {
            $this->updateAll(['primary' => false], [
                'owner_id' => $entity->owner_id,
                'NOT' => ['id' => $entity->id],
            ]);
        }
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
     * Filters accounts by query string
     *
     * @param array<string, mixed> $filter Filter array.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
    {
        $ret = [];

        return $ret;
    }

    /**
     * Returns list of accounts for specified owner
     *
     * @param string $ownerId Company Id.
     * @param bool $showActiveOnly Show only active accounts.
     * @return array<\Expenses\Model\Entity\PaymentsAccount>
     */
    public function listForOwner(string $ownerId, bool $showActiveOnly = false): array
    {
        $conditions = ['PaymentsAccounts.owner_id' => $ownerId];
        if ($showActiveOnly) {
            $conditions['PaymentsAccounts.active'] = true;
        }
        $ret = $this->find('list')->where($conditions)->toArray();

        return $ret;
    }
}
