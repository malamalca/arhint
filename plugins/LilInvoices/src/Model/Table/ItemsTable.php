<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Items Model
 *
 * @property \LilInvoices\Model\Table\VatsTable $Vats
 * @method \LilInvoices\Model\Entity\Item get(string $id)
 * @method \LilInvoices\Model\Entity\Item newEmptyEntity()
 * @method \LilInvoices\Model\Entity\Item patchEntity($entity, array $data = [])
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
            'className' => 'LilInvoices.Vats',
        ]);
        $this->belongsToMany('Invoices', [
            'foreignKey' => 'item_id',
            'targetForeignKey' => 'invoice_id',
            'joinTable' => 'invoices_items',
            'className' => 'LilInvoices.Invoices',
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
