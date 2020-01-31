<?php
declare(strict_types=1);

namespace LilInvoices\Policy;

/**
 * InvoicesTable Policy Resolver
 */
class InvoicesTablePolicy
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
        return $query->where(['Invoices.owner_id' => $user->company_id]);
    }
}
