<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * BankStatementEntriesTable Policy Resolver
 */
class BankStatementEntriesTablePolicy
{
    /**
     * Index scope – entries are visible to all authenticated plugin users of the same company.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->innerJoinWith('BankStatements', function (SelectQuery $q) use ($user) {
            return $q->where(['BankStatements.owner_id' => $user->company_id]);
        });
    }
}
