<?php
declare(strict_types=1);

namespace LilCrm\Policy;

/**
 * Contact Policy Resolver
 */
class ContactPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\Contact $contact Contact
     * @return bool
     */
    public function canView($user, $contact)
    {
        return $user->company_id == $contact->owner_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\Contact $contact Contact
     * @return bool
     */
    public function canEdit($user, $contact)
    {
        return ($user->company_id == $contact->owner_id) && $user->hasRole('admin');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilCrm\Model\Entity\Contact $contact Contact
     * @return bool
     */
    public function canDelete($user, $contact)
    {
        return ($user->company_id == $contact->owner_id) && $user->hasRole('admin');
    }
}
