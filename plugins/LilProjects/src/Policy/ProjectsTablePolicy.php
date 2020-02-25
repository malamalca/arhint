<?php
declare(strict_types=1);

namespace LilProjects\Policy;

/**
 * ProjectsTable Policy Resolver
 */
class ProjectsTablePolicy
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
        return $query->where(['Projects.owner_id' => $user->company_id]);
    }
}
