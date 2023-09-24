<?php
declare(strict_types=1);

namespace Crm\Policy;

use App\Model\Entity\User;
use Crm\Model\Entity\Contact;

/**
 * Contact Policy Resolver
 */
class ContactPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\Contact $contact Contact
     * @return bool
     */
    public function canView(User $user, Contact $contact): bool
    {
        return $user->company_id == $contact->owner_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\Contact $contact Contact
     * @return bool
     */
    public function canEdit(User $user, Contact $contact): bool
    {
        return $user->company_id == $contact->owner_id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Crm\Model\Entity\Contact $contact Contact
     * @return bool
     */
    public function canDelete(User $user, Contact $contact): bool
    {
        return $user->company_id == $contact->owner_id;
    }
}
