<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DocumentsClients Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Contacts
 * @method \Documents\Model\Entity\DocumentsClient get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\DocumentsClient newEntity(array $data = [], array $options = [])
 * @method \Documents\Model\Entity\DocumentsClient newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsClient patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsClientsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_clients');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Documents', [
            'foreignKey' => 'document_id',
            'className' => 'Documents.Documents',
        ]);
        $this->belongsTo('Contacts', [
            'foreignKey' => 'contact_id',
            'className' => 'Documents.Contacts',
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
            ->allowEmptyString('id', 'create');

        $validator
            ->notEmptyString('kind');

        $validator
            ->notEmptyString('title');

        $validator
            ->allowEmptyString('person');

        $validator
            ->allowEmptyString('phone');

        $validator
            ->allowEmptyString('fax');

        $validator
            ->add('email', 'valid', ['rule' => 'email'])
            ->allowEmptyString('email');

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
        //$rules->add($rules->existsIn(['contact_id'], 'Contacts'));
        return $rules;
    }
}
