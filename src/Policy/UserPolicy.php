<?php
declare(strict_types=1);

namespace App\Policy;

/**
 * User Policy Resolver
 */
class UserPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\User $user User
     * @return bool
     */
    public function canView($authUser, $user)
    {
        return $authUser->company_id == $user->company_id;
    }

    /**
     * Authorize properties action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\User $user User
     * @return bool
     */
    public function canProperties($authUser, $user)
    {
        return $authUser->id == $user->id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\User $user User
     * @return bool
     */
    public function canEdit($authUser, $user)
    {
        return ($authUser->company_id == $user->company_id) && $authUser->hasRole('admin');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\User $user User
     * @return bool
     */
    public function canDelete($authUser, $user)
    {
        return ($authUser->company_id == $user->company_id) && $authUser->hasRole('admin');
    }
}
