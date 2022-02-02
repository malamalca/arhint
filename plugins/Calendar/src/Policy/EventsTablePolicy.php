<?php
declare(strict_types=1);

namespace Calendar\Policy;

/**
 * EventsTable Policy Resolver
 */
class EventsTablePolicy
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
        return $query;
    }
}
