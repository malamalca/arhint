<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ContactsAddresses Model
 *
 * @property \Crm\Model\Table\ContactsTable $Contacts
 * @method \Crm\Model\Entity\ContactsAddress newEmptyEntity()
 * @method \Crm\Model\Entity\ContactsAddress newEntity(array $data, array $options = [])
 */
class ContactsAddressesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('contacts_addresses');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Crm.Contacts', [
            'foreignKey' => 'contact_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('AdremasContacts', [
            'foreignKey' => 'contacts_address_id',
            'className' => 'Crm\Model\Table\AdremasContactsTable',
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
            //->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            ->allowEmptyString('contact_id')
            ->add('primary', 'valid', ['rule' => 'boolean'])
            //->requirePresence('primary', 'create')
            ->notEmptyString('primary')
            ->notEmptyString('kind')
            ->notEmptyString('street')

            ->allowEmptyString('zip')
            ->allowEmptyString('city')
            ->allowEmptyString('country');

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
        $rules->add($rules->existsIn(['contact_id'], 'Contacts'));

        // check that only one entry for specified kind
        $rules->add(function ($entity, $options) {
            $conditions = [
                'contact_id' => $entity->contact_id,
                'kind' => $entity->kind,
            ];
            if ($entity->isNew() === false) {
                $conditions['NOT'] = ['id' => $entity->id];
            }

            return !$this->exists($conditions);
        }, ['errorField' => 'kind', 'message' => 'kindOccupied']);

        return $rules;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Crm\Model\Entity\ContactsAddress $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return bool
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options): bool
    {
        if (!$this->exists(['contact_id' => $entity->contact_id, 'primary' => true])) {
            $entity->primary = true;
        }
        if (!empty($entity->country_code) && empty($entity->country)) {
            $countries = Configure::read('Crm.countries');
            if (isset($countries[$entity->country_code])) {
                $entity->country = $countries[$entity->country_code];
            }
        }

        return true;
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Crm\Model\Entity\ContactsAddress $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if ($entity->primary) {
            $this->updateAll(['primary' => false], [
                'contact_id' => $entity->contact_id,
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
        /** @var \Crm\Model\Entity\ContactsAddress $entity */
        $entity = $this->get($entityId, ['fields' => 'contact_id']);

        return $this->Contacts->isOwnedBy($entity->contact_id, $ownerId);
    }
}
