<?php
declare(strict_types=1);

namespace Crm\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * AdremasTable Policy Resolver
 */
class AdremasTablePolicy
{
    /**
     * Contacts scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->where(['Adremas.owner_id' => $user->company_id]);
    }
}
