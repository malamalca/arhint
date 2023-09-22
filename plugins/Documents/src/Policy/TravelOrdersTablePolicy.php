<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * TravelOrdersTable Policy Resolver
 */
class TravelOrdersTablePolicy
{
    /**
     * Index scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
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
