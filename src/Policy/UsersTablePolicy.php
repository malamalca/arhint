<?php
declare(strict_types=1);

namespace App\Policy;

/**
 * UsersTable Policy Resolver
 */
class UsersTablePolicy
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
        return $query->where(['Users.company_id' => $user->company_id]);
    }
}
