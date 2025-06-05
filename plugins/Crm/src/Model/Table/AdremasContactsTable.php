<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AdremasContacts Model
 *
 * @property \Crm\Model\Table\AdremasTable $Adremas
 * @method \Crm\Model\Entity\AdremasContact get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 */
class AdremasContactsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('adremas_contacts');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Adremas', [
            'foreignKey' => 'adrema_id',
            'className' => 'Crm.Adremas',
        ]);
        $this->belongsTo('Contacts', [
            'foreignKey' => 'contact_id',
            'className' => 'Crm.Contacts',
        ]);
        $this->belongsTo('ContactsAddresses', [
            'foreignKey' => 'contacts_address_id',
            'className' => 'Crm.ContactsAddresses',
        ]);
        $this->belongsTo('ContactsEmails', [
            'foreignKey' => 'contacts_email_id',
            'className' => 'Crm.ContactsEmails',
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
            //->add('owner_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('owner_id')
            ->add('adrema_id', 'valid', ['rule' => 'uuid'])
            ->notEmptyString('adrema_id')
            ->notEmptyString('contact_id')
            //->add('contacts_address_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('contacts_address_id');

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
        $rules->add($rules->existsIn(['adrema_id'], 'Adremas'));
        $rules->add($rules->existsIn(['contact_id'], 'Contacts'));
        $rules->add($rules->existsIn(['contacts_address_id'], 'ContactsAddresses'));
        $rules->add($rules->existsIn(['contacts_email_id'], 'ContactsEmails'));
        return $rules;
    }

    /**
     * beforeMarshal method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $data Post data.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!empty($data['data'])) {
            $data['descript'] = json_encode($data['data']);
            $event->setResult($data);
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
        /** @var \Crm\Model\Entity\AdremasContact $entity */
        $entity = $this->get($entityId, ['fields' => 'adrema_id']);

        return $this->Adremas->isOwnedBy($entity->adrema_id, $ownerId);
    }
}
