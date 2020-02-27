<?php
declare(strict_types=1);

namespace LilProjects\Policy;

/**
 * ProjectsStatus Policy Resolver
 */
class ProjectsStatusPolicy
{
    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsStatus $entity Entity
     * @return bool
     */
    public function canEdit($user, $entity)
    {
        return $entity->owner_id == $user->company_id;
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \LilProjects\Model\Entity\ProjectsStatus $entity Entity
     * @return bool
     */
    public function canDelete($user, $entity)
    {
        return $entity->owner_id == $user->company_id;
    }
}
