<?php
declare(strict_types=1);

namespace Expenses\Policy;

/**
 * PaymentsTable Policy Resolver
 */
class PaymentsTablePolicy
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
        return $query->where(['Payments.owner_id' => $user->company_id]);
    }
}
