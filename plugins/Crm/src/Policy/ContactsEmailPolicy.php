<?php
declare(strict_types=1);

namespace Crm\Policy;

use Cake\ORM\TableRegistry;

/**
 * ContactsEmail Policy Resolver
 */
class ContactsEmailPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\ContactsEmail $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\ContactsEmail $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \Crm\Model\Table\ContactsTable $contactsTable */
        $contactsTable = TableRegistry::getTableLocator()->get('Crm.Contacts');

        return $contactsTable->isOwnedBy($entity->contact_id, $user->company_id);
    }
}