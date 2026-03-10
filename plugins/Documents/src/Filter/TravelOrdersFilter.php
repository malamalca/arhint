<?php
declare(strict_types=1);

namespace Documents\Filter;

use App\Filter\Filter;
use Cake\Validation\Validator;
use Documents\Model\Entity\TravelOrder;

/**
 * TravelOrders Filter
 *
 * Parses the `q` query string and exposes filter state for
 * the TravelOrders index view. Supports status, employee and sort fields.
 */
class TravelOrdersFilter extends Filter
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        $this->addField('status');
        $this->addField('employee');
        $this->addField('sort');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        // 'open' and 'closed' are virtual aggregates; individual statuses also accepted
        $allowedStatuses = array_merge(['open', 'closed'], array_keys(TravelOrder::statusLabels()));

        $validator
            ->allowEmptyString('status')
            ->add('status', 'inList', [
                'rule' => ['inList', $allowedStatuses],
                'message' => __d('documents', 'Invalid status value.'),
            ]);

        $validator->allowEmptyString('employee');

        $validator
            ->allowEmptyString('sort')
            ->add('sort', 'inList', [
                'rule' => ['inList', ['newest', 'oldest', 'date-desc', 'date-asc', 'employee']],
                'message' => __d('documents', 'Invalid sort value.'),
            ]);

        return $validator;
    }
}
