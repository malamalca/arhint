<?php
declare(strict_types=1);

namespace LilExpenses\Policy;

/**
 * PaymentsAccountsTable Policy Resolver
 */
class PaymentsAccountsTablePolicy
{
    /**
     * Index scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    public function scopeIndex($user, $query)
    {
        return $query->where(['PaymentsAccounts.owner_id' => $user->company_id]);
    }
}
