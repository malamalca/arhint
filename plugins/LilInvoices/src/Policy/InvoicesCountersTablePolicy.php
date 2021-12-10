<?php
declare(strict_types=1);

namespace LilInvoices\Policy;

/**
 * InvoicesCountersCountersTable Policy Resolver
 */
class InvoicesCountersTablePolicy
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
        $query->where(['InvoicesCounters.owner_id' => $user->company_id]);

        if (!$user->hasRole('admin')) {
            $query->join([
                'table' => 'invoices_counters_users',
                'alias' => 'c',
                'type' => 'INNER',
                'conditions' => [
                    'c.counter_id = InvoicesCounters.id',
                    'c.user_id' => $user->id
                ],
            ]);
        }

        return $query;
    }
}
