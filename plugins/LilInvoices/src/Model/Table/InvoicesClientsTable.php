<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * InvoicesClients Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Contacts
 *
 * @method \LilInvoices\Model\Entity\InvoicesClient get(string $id)
 * @method \LilInvoices\Model\Entity\InvoicesClient newEntity(array $data = [])
 * @method \LilInvoices\Model\Entity\InvoicesClient newEmptyEntity()
 * @method \LilInvoices\Model\Entity\InvoicesClient patchEntity($entity, array $data = [])
 */
class InvoicesClientsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices_clients');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Invoices', [
            'foreignKey' => 'invoice_id',
            'className' => 'LilInvoices.Invoices',
        ]);
        $this->belongsTo('Contacts', [
            'foreignKey' => 'contact_id',
            'className' => 'LilInvoices.Contacts',
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
