<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\DocumentsTemplate;

/**
 * DocumentsTemplate Policy Resolver
 */
class DocumentsTemplatePolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsTemplate $entity Entity
     * @return bool
     */
    public function canView(User $user, DocumentsTemplate $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsTemplate $entity Entity
     * @return bool
     */
    public function canEdit(User $user, DocumentsTemplate $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsTemplate $entity Entity
     * @return bool
     */
    public function canDelete(User $user, DocumentsTemplate $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }
}
