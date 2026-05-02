<?php
declare(strict_types=1);

namespace Expenses\Filter;

use App\Filter\Filter;
use Cake\Validation\Validator;
use Expenses\Model\Table\BookingOrdersTable;

/**
 * BookingOrders Filter
 *
 * Parses the `q` query string and exposes filter state for
 * the BookingOrders index view. Supports status, opener and sort fields.
 */
class BookingOrdersFilter extends Filter
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->addField('status');
        $this->addField('opener');
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
        $allowedStatuses = array_merge(['open', 'closed'], BookingOrdersTable::STATUSES);

        $validator
            ->allowEmptyString('status')
            ->add('status', 'inList', [
                'rule' => ['inList', $allowedStatuses],
                'message' => __d('expenses', 'Invalid status value.'),
            ]);

        $validator->allowEmptyString('opener');

        $validator
            ->allowEmptyString('sort')
            ->add('sort', 'inList', [
                'rule' => ['inList', ['newest', 'oldest', 'date-desc', 'date-asc', 'opener']],
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
