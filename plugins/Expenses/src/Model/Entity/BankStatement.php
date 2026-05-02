<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * BankStatement Entity
 *
 * @property string $id
 * @property string $owner_id
 * @property string $user_id
 * @property string $no
 * @property string|null $kind
 * @property string $iban
 * @property \Cake\I18n\Date $dat_issue
 * @property string $currency
 * @property \Cake\I18n\DateTime $dat_import
 * @property int|null $seq_no
 * @property string $total_credit
 * @property string $total_debit
 * @property int $count_credit
 * @property int $count_debit
 * @property string $saldo
 * @property string|null $balance
 * @property \App\Model\Entity\User $owner
 * @property \App\Model\Entity\User $user
 * @property \Expenses\Model\Entity\BankStatementEntry[] $bank_statement_entries
 */
class BankStatement extends Entity
{
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Virtual title used by autocomplete and linked-document resolution.
     *
     * @return string
     */
    protected function _getTitle(): string
    {
        return h((string)$this->iban) . ' ' . h((string)$this->dat_issue);
    }
}
