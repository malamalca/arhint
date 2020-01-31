<?php
declare(strict_types=1);

namespace LilCrm\Policy;

/**
 * AdremasTable Policy Resolver
 */
class AdremasTablePolicy
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
        return $query->where(['Adremas.owner_id' => $user->company_id]);
    }
}
