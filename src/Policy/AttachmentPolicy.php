<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Attachment;
use App\Model\Entity\User;

/**
 * Attachment Policy Resolver
 */
class AttachmentPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\Attachment $attachment DashboardNote
     * @return bool
     */
    public function canEdit(User $authUser, Attachment $attachment): bool
    {
        return $authUser->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\Attachment $attachment DashboardNote
     * @return bool
     */
    public function canDelete(User $authUser, Attachment $attachment): bool
    {
        return $authUser->hasRole('editor');
    }
}
