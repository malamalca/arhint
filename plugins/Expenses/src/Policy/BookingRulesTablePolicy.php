<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * BookingRulesTable Policy Resolver
 */
class BookingRulesTablePolicy
{
    /**
     * Index scope – restrict to rules belonging to the current user's company.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->where(['BookingRules.owner_id' => $user->company_id]);
    }
}
