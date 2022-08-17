<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Items Model
 *
 * @property \Documents\Model\Table\VatsTable $Vats
 * @method \Documents\Model\Entity\Item get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\Item newEmptyEntity()
 * @method \Documents\Model\Entity\Item patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class ItemsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('items');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Vats', [
            'foreignKey' => 'vat_id',
            'className' => 'Documents.Vats',
        ]);
        $this->belongsToMany('Documents', [
            'foreignKey' => 'item_id',
            'targetForeignKey' => 'document_id',
            'joinTable' => 'invoices_items',
            'className' => 'Documents.Documents',
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
            ->allowEmptyString('id', 'create')
            ->add('vat_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('vat_id')
            ->notEmptyString('descript')
            ->add('qty', 'valid', ['rule' => 'decimal'])
            ->requirePresence('qty', 'create')
            ->notEmptyString('qty')
            ->allowEmptyString('unit')
            ->add('price', 'valid', ['rule' => 'decimal'])
            ->requirePresence('price', 'create')
            ->notEmptyString('price')
            ->add('discount', 'valid', ['rule' => 'decimal'])
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
        $rules->add($rules->existsIn(['vat_id'], 'Vats'));

        return $rules;
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
}
