<?php
declare(strict_types=1);

namespace Tasks\Policy;

/**
 * TasksTable Policy Resolver
 */
class TasksTablePolicy
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
        return $query->where(['Tasks.owner_id' => $user->company_id]);
    }
}
