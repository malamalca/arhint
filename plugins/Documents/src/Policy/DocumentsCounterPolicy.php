<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\DocumentsCounter;

/**
 * DocumentsCounter Policy Resolver
 */
class DocumentsCounterPolicy
{
    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsCounter $entity Entity
     * @return bool
     */
    public function canView(User $user, DocumentsCounter $entity): bool
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsCounter $entity Entity
     * @return bool
     */
    public function canEdit(User $user, DocumentsCounter $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\DocumentsCounter $entity Entity
     * @return bool
     */
    public function canDelete(User $user, DocumentsCounter $entity): bool
    {
        return $entity->owner_id == $user->company_id && $user->hasRole('editor');
    }
}
