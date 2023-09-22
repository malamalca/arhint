<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;

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
    public function canView(User $authUser, User $user): bool
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
    public function canProperties(User $authUser, User $user): bool
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
    public function canEdit(User $authUser, User $user): bool
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
    public function canDelete(User $authUser, User $user): bool
    {
        return ($authUser->company_id == $user->company_id) && $authUser->hasRole('admin');
    }
}
