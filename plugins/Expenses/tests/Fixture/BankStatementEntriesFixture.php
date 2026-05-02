<?php
declare(strict_types=1);

namespace Expenses\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * BankStatementEntriesFixture
 */
class BankStatementEntriesFixture extends TestFixture
{
    /**
     * Records
     *
     * @var array
     */
    public array $records = [
        [
            'id' => BANK_STATEMENT_ENTRY_1,
            'statement_id' => BANK_STATEMENT_1,
            'no' => '281884527',
            'client' => 'PREHODNI DAVČNI PODRAČUN - PRORAČUN DRŽAVE',
            'descript' => 'Akontacija dohodnine za FEB 2026',
            'credit' => '0.00',
            'debit' => '106.43',
            'iban' => 'SI56011008881000030',
            'ref' => 'SI1955736645-40002',
            'dat_issue' => '2026-03-13',
        ],
        [
            'id' => BANK_STATEMENT_ENTRY_2,
            'statement_id' => BANK_STATEMENT_1,
            'no' => '281884528',
            'client' => 'PREHODNI DAVČNI PODRAČUN - ZPIZ',
            'descript' => 'Prispevki za ZPIZ za FEB 2026',
            'credit' => '0.00',
            'debit' => '447.94',
            'iban' => 'SI56011008882000003',
            'ref' => 'SI1955736645-44008',
            'dat_issue' => '2026-03-13',
        ],
    ];
}
