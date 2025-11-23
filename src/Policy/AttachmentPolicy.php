<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Attachment;
use App\Model\Entity\User;
use Cake\ORM\TableRegistry;

/**
 * Attachment Policy Resolver
 */
class AttachmentPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\Attachment $attachment Attachment
     * @return bool
     */
    public function canEdit(User $authUser, Attachment $attachment): bool
    {
        return $authUser->hasRole('editor');
    }

    /**
     * Authorize view/preview action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\Attachment $attachment Attachment
     * @return bool
     */
    public function canView(User $authUser, Attachment $attachment): bool
    {
        /** @var \App\Model\Table\AttachmentsTable $AttachmentsTable */
        $AttachmentsTable = TableRegistry::getTableLocator()->get('Attachments');

        return $AttachmentsTable->isOwnedBy($attachment, $authUser->company_id);
    }

    /**
     * Authorize download action
     *
     * @param \App\Model\Entity\User $authUser User
     * @param \App\Model\Entity\Attachment $attachment Attachment
     * @return bool
     */
    public function canDownload(User $authUser, Attachment $attachment): bool
    {
        /** @var \App\Model\Table\AttachmentsTable $AttachmentsTable */
        $AttachmentsTable = TableRegistry::getTableLocator()->get('Attachments');

        return $AttachmentsTable->isOwnedBy($attachment, $authUser->company_id);
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
