<?php
declare(strict_types=1);

namespace App\Test\TestCase\Filter;

use App\Filter\Filter;
use Cake\Validation\Validator;

/**
 * Minimal concrete subclass used for testing the abstract Filter base class.
 */
class TestFilter extends Filter
{
    public function initialize(): void
    {
        $this->addField('status');
        $this->addField('kind');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator->scalar('status')->allowEmptyString('status');
        $validator->scalar('kind')->allowEmptyString('kind');

        return $validator;
    }
}
