<?php
declare(strict_types=1);

namespace Expenses\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * PartnersTable Policy Resolver
 */
class PartnersTablePolicy
{
    /**
     * Index scope – partners are a global reference list, visible to all plugin users.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query;
    }
}
