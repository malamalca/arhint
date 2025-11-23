<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ContactsEmails Model
 *
 * @property \Crm\Model\Table\ContactsTable $Contacts
 * @method \Crm\Model\Entity\ContactsEmail newEmptyEntity()
 * @method \Crm\Model\Entity\ContactsEmail newEntity(array $data, array $options = [])
 */
class ContactsEmailsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('contacts_emails');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Crm.Contacts', [
            'foreignKey' => 'contact_id',
            'joinType' => 'INNER',
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
            ->add('email', 'valid', ['rule' => 'email'])
            ->allowEmptyString('email')
            ->allowEmptyString('kind');

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
        //$rules->add($rules->isUnique(['email']));
        $rules->add($rules->existsIn(['contact_id'], 'Contacts'));

        // check that only one entry for specified kind
        /*$rules->add(function ($entity, $options) {
            $conditions = [
                'contact_id' => $entity->contact_id,
                'kind' => $entity->kind,
            ];
            if ($entity->isNew() === false) {
                $conditions['NOT'] = ['id' => $entity->id];
            }

            return !$this->exists($conditions);
        }, ['errorField' => 'kind', 'message' => 'kindOccupied']);*/

        return $rules;
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Crm\Model\Entity\ContactsEmail $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if ($entity->primary) {
            $this->updateAll(['primary' => false], [
                'contact_id' => $entity->contact_id,
                'id !=' => $entity->id,
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
        /** @var \Crm\Model\Entity\ContactsEmail $entity */
        $entity = $this->get($entityId, ['fields' => 'contact_id']);

        return $this->Contacts->isOwnedBy($entity->contact_id, $ownerId);
    }
}
