<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * BookingOrderEntriesTable Policy Resolver
 */
class BookingOrderEntriesTablePolicy
{
    /**
     * Index scope – entries are visible to all authenticated plugin users.
     * Filtering is handled at controller level via the parent booking order.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->innerJoinWith('BookingOrders', function (SelectQuery $q) use ($user) {
            return $q->where(['BookingOrders.owner_id' => $user->company_id]);
        });
    }
}
