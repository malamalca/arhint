<?php
declare(strict_types=1);

namespace Documents\Policy;

use Cake\ORM\TableRegistry;

/**
 * DocumentsAttachment Policy Resolver
 */
class DocumentsAttachmentPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity
     * @return bool
     */
    public function canView($user, $entity)
    {
        /** @var \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachmentsTable */
        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');

        return $DocumentsAttachmentsTable->isOwnedBy($entity, $user->company_id);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        /** @var \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachmentsTable */
        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');

        return $DocumentsAttachmentsTable->isOwnedBy($entity, $user->company_id) && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        /** @var \Documents\Model\Table\DocumentsAttachmentsTable $DocumentsAttachmentsTable */
        $DocumentsAttachmentsTable = TableRegistry::getTableLocator()->get('Documents.DocumentsAttachments');

        return $DocumentsAttachmentsTable->isOwnedBy($entity, $user->company_id) && $user->hasRole('editor');
    }
}
