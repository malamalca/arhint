<?php
declare(strict_types=1);

namespace LilExpenses\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PaymentsAccounts Model
 *
 * @method \LilExpenses\Model\Entity\PaymentsAccount get(string $id)
 * @method \LilExpenses\Model\Entity\PaymentsAccount newEmptyEntity()
 * @method \LilExpenses\Model\Entity\PaymentsAccount patchEntity($entity, array $data = [])
 */
class PaymentsAccountsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
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
            ->allowEmpty('id', 'create');

        $validator
            ->notEmpty('title');

        $validator
            ->add('primary', 'valid', ['rule' => 'boolean'])
            ->requirePresence('primary', 'create')
            ->notEmpty('primary');

        $validator
            ->add('active', 'valid', ['rule' => 'boolean'])
            ->requirePresence('active', 'create')
            ->notEmpty('active');

        return $validator;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilExpenses\Model\Entity\PaymentsAccount $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return bool
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if (!$this->exists(['owner_id' => $entity->owner_id, 'primary' => true])) {
            $entity->primary = true;
        }

        return true;
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilExpenses\Model\Entity\PaymentsAccount $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
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
    public function isOwnedBy($entityId, $ownerId)
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * Filters accounts by query string
     *
     * @param array $filter Filter array.
     * @return array
     */
    public function filter(&$filter)
    {
        $ret = [];

        return $ret;
    }

    /**
     * Returns list of accounts for specified owner
     *
     * @param string $ownerId Company Id.
     * @param bool $showActiveOnly Show only active accounts.
     * @return array
     */
    public function listForOwner($ownerId, $showActiveOnly = false)
    {
        $conditions = ['PaymentsAccounts.owner_id' => $ownerId];
        if ($showActiveOnly) {
            $conditions['PaymentsAccounts.active'] = true;
        }
        $ret = $this->find('list')->where($conditions)->toArray();

        return $ret;
    }
}
