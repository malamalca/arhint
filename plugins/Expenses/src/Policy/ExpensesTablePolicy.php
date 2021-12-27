<?php
declare(strict_types=1);

namespace Expenses\Policy;

/**
 * ExpensesTable Policy Resolver
 */
class ExpensesTablePolicy
{
    /**
     * Contacts scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    public function scopeIndex($user, $query)
    {
        return $query->where(['Expenses.owner_id' => $user->company_id]);
    }
}
