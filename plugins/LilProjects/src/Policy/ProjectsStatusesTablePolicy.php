<?php
declare(strict_types=1);

namespace LilProjects\Policy;

/**
 * ProjectsStatusesTable Policy Resolver
 */
class ProjectsStatusesTablePolicy
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
        return $query->where(['ProjectsStatuses.owner_id' => $user->company_id]);
    }
}
