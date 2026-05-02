<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BankStatementEntry Entity
 *
 * @property string $id
 * @property string $statement_id
 * @property string|null $no
 * @property string|null $client
 * @property string|null $descript
 * @property string $credit
 * @property string $debit
 * @property string|null $iban
 * @property string|null $ref
 * @property \Cake\I18n\Date|null $dat_issue
 * @property \Expenses\Model\Entity\BankStatement $bank_statement
 */
class BankStatementEntry extends Entity
{
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];
}
