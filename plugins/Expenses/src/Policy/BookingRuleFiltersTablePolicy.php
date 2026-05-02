<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * BookingRuleFiltersTable Policy Resolver
 */
class BookingRuleFiltersTablePolicy
{
    /**
     * Index scope – filters are visible to all authenticated plugin users
     * whose company owns the parent rule.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->innerJoinWith('BookingRules', function (SelectQuery $q) use ($user) {
            return $q->where(['BookingRules.owner_id' => $user->company_id]);
        });
    }
}
