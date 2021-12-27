<?php
declare(strict_types=1);

namespace Expenses\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PaymentsExpenses Model
 *
 * @property \Cake\ORM\Association\BelongsTo $Payments
 * @property \Cake\ORM\Association\BelongsTo $Expenses
 */
class PaymentsExpensesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payments_expenses');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Payments', [
            'foreignKey' => 'payment_id',
            'className' => 'Expenses.Payments',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Expenses', [
            'foreignKey' => 'expense_id',
            'className' => 'Expenses.Expenses',
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
            ->add('id', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('id', 'create');

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
        $rules->add($rules->existsIn(['payment_id'], 'Payments'));
        $rules->add($rules->existsIn(['expense_id'], 'Expenses'));

        return $rules;
    }
}
