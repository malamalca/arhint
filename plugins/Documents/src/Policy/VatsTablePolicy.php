<?php
declare(strict_types=1);

namespace Documents\Policy;

/**
 * VatsTable Policy Resolver
 */
class VatsTablePolicy
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
        return $query->where(['Vats.owner_id' => $user->company_id]);
    }
}
