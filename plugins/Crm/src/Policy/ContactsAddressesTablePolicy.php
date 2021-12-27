<?php
declare(strict_types=1);

namespace Crm\Policy;

/**
 * ContactsAddressesTable Policy Resolver
 */
class ContactsAddressesTablePolicy
{
    /**
     * Contacts scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    public function scopeIndex($user, $query)
    {
        return $query->where(['Contacts.owner_id' => $user->company_id]);
    }
}
