<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoicesItems Model
 *
 * @method \LilInvoices\Model\Entity\InvoicesItem get(string $id)
 * @method \LilInvoices\Model\Entity\InvoicesItem newEmptyEntity()
 * @method \LilInvoices\Model\Entity\InvoicesItem patchEntity($entity, array $data = [])
 */
class InvoicesItemsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices_items');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Invoices', [
            'foreignKey' => 'invoice_id',
            'className' => 'LilInvoices\Model\Table\InvoicesTable',
        ]);
        /*$this->belongsTo('Items', [
            'foreignKey' => 'item_id',
            'className' => 'LilInvoices\Model\Table\ItemsTable'
        ]);*/
        $this->belongsTo('Vats', [
            'foreignKey' => 'vat_id',
            'className' => 'LilInvoices\Model\Table\VatsTable',
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
            ->allowEmptyString('invoice_id', 'create')
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
        $rules->add($rules->existsIn(['invoice_id'], 'Invoices'));
        //$rules->add($rules->existsIn(['item_id'], 'Items'));
        $rules->add($rules->existsIn(['vat_id'], 'Vats'));

        return $rules;
    }
}
