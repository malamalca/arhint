<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ContactsLogs Model
 *
 * @property \Crm\Model\Table\ContactsTable $Contacts
 * @method \Crm\Model\Entity\ContactsLog newEmptyEntity()
 * @method \Crm\Model\Entity\ContactsLog newEntity(array $data, array $options = [])
 */
class ContactsLogsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('contacts_logs');
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
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            ->notEmptyString('contact_id');

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

        return $rules;
    }
}
