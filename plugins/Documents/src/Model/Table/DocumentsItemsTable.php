<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DocumentsItems Model
 *
 * @method \Documents\Model\Entity\DocumentsItem get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\DocumentsItem newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsItem patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsItemsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_items');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Documents', [
            'foreignKey' => 'document_id',
            'className' => 'Documents\Model\Table\DocumentsTable',
        ]);
        /*$this->belongsTo('Items', [
            'foreignKey' => 'item_id',
            'className' => 'Documents\Model\Table\ItemsTable'
        ]);*/
        $this->belongsTo('Vats', [
            'foreignKey' => 'vat_id',
            'className' => 'Documents\Model\Table\VatsTable',
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
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('id', 'create')
            ->allowEmptyString('document_id', 'create')
            ->allowEmptyString('item_id', 'create')

            ->add('vat_id', 'valid', ['rule' => 'uuid'])
            ->notEmptyString('vat_id')

            ->requirePresence('descript', 'create')
            ->notEmptyString('descript')
            //->add('qty', 'valid', ['rule' => 'decimal'])

            ->requirePresence('qty', 'create')
            ->notEmptyString('qty')

            ->notEmptyString('unit')

            //->add('price', 'valid', ['rule' => 'decimal'])
            ->requirePresence('price', 'create')
            ->notEmptyString('price')

            //->add('discount', 'valid', ['rule' => 'decimal'])
            ->requirePresence('discount', 'create')
            ->notEmptyString('discount');

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
        $rules->add($rules->existsIn(['document_id'], 'Documents'));
        //$rules->add($rules->existsIn(['item_id'], 'Items'));
        $rules->add($rules->existsIn(['vat_id'], 'Vats'));

        return $rules;
    }
}
