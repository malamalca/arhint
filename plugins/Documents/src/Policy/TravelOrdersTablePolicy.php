<?php
declare(strict_types=1);

namespace Documents\Policy;

/**
 * TravelOrdersTable Policy Resolver
 */
class TravelOrdersTablePolicy
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
        $query->where(['TravelOrders.owner_id' => $user->company_id]);

        if (!$user->hasRole('admin')) {
            $query->join([
                'table' => 'documents_counters_users',
                'alias' => 'c',
                'type' => 'INNER',
                'conditions' => [
                    'c.counter_id = TravelOrders.counter_id',
                    'c.user_id' => $user->id,
                ],
            ]);
        }

        return $query;
    }
}
