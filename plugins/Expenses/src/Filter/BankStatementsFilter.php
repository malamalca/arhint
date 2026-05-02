<?php
declare(strict_types=1);

namespace Expenses\Filter;

use App\Filter\Filter;
use Cake\Validation\Validator;

/**
 * BankStatements Filter
 *
 * Parses the `q` query string and exposes filter state for
 * the BankStatements index view. Supports iban, span and sort fields.
 */
class BankStatementsFilter extends Filter
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->addField('iban');
        $this->addField('sort');
        $this->addField('span');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->allowEmptyString('iban');

        $validator
            ->allowEmptyString('sort')
            ->add('sort', 'inList', [
                'rule' => ['inList', ['date-asc', 'date-desc']],
                'message' => __d('expenses', 'Invalid sort value.'),
            ]);

        $validator
            ->allowEmptyString('span')
            ->add('span', 'inList', [
                'rule' => ['inList', ['this-month', 'prev-month', 'last-3-months', 'this-year']],
                'message' => __d('expenses', 'Invalid span value.'),
            ]);

        return $validator;
    }
}
