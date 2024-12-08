<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\DashboardNote;
use App\Model\Entity\User;

/**
 * DashboardNote Policy Resolver
 */
class DashboardNotePolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\DashboardNote $note DashboardNote
     * @return bool
     */
    public function canEdit(User $authUser, DashboardNote $note): bool
    {
        return ($authUser->id == $note->user_id) || $authUser->hasRole('admin');
    }
}
