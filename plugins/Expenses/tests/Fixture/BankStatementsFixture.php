<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BankStatementsFixture
 */
class BankStatementsFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BANK_STATEMENT_1,
            'owner_id' => COMPANY_FIRST,
            'user_id' => USER_ADMIN,
            'no' => '978-2026-00034',
            'kind' => 'camt.053',
            'iban' => 'SI56610000005092459',
            'dat_issue' => '2026-03-13',
            'currency' => 'EUR',
            'dat_import' => '2026-03-14 10:00:00',
            'total_credit' => '0.00',
            'total_debit' => '2833.44',
            'count_credit' => 0,
            'count_debit' => 9,
            'saldo' => '-2833.44',
        ],
        [
            'id' => BANK_STATEMENT_2,
            'owner_id' => COMPANY_FIRST,
            'user_id' => USER_ADMIN,
            'no' => '978-2026-00033',
            'kind' => 'camt.053',
            'iban' => 'SI56610000005092459',
            'dat_issue' => '2026-03-12',
            'currency' => 'EUR',
            'dat_import' => '2026-03-13 09:00:00',
            'total_credit' => '5000.00',
            'total_debit' => '1200.00',
            'count_credit' => 3,
            'count_debit' => 5,
            'saldo' => '3800.00',
        ],
    ];
}
