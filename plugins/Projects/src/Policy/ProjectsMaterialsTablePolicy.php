<?php
declare(strict_types=1);

namespace Projects\Policy;

/**
 * ProjectsMaterialsTable Policy Resolver
 */
class ProjectsMaterialsTablePolicy
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
        return $query->where(['ProjectsMaterials.owner_id' => $user->company_id]);
    }
}
