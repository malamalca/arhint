<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoicesTaxes Model
 *
 * @method \Documents\Model\Entity\InvoicesTax get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\InvoicesTax newEmptyEntity()
 * @method \Documents\Model\Entity\InvoicesTax patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class InvoicesTaxesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices_taxes');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Invoices', [
            'foreignKey' => 'invoice_id',
            'className' => 'Documents\Model\Table\InvoicesTable',
        ]);
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
            ->add('invoice_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('invoice_id')
            ->add('vat_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('vat_id')
            ->add('vat_percent', 'valid', ['rule' => 'decimal'])
            ->requirePresence('vat_percent', 'create')
            ->add('base', 'valid', ['rule' => 'decimal'])
            ->requirePresence('base', 'create')
            ->notEmptyString('base');

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
        $rules->add($rules->existsIn(['vat_id'], 'Vats'));

        return $rules;
    }
}
