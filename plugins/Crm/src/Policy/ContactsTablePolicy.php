<?php
declare(strict_types=1);

namespace Crm\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * ContactsTable Policy Resolver
 */
class ContactsTablePolicy
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
        return $query->where(['Contacts.owner_id' => $user->company_id]);
    }
}
