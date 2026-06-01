<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\Log;
use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Projects\Policy\ProjectAccessTrait;
use Throwable;

/**
 * Log Policy Resolver for App.Logs entries.
 */
class LogPolicy
{
    use ProjectAccessTrait;

    /**
     * Authorize view action
     *
     * @param \App\Model\Entity\User $user User
     * @param \App\Model\Entity\Log $entity Log entity
     * @return bool
     */
    public function canView(User $user, Log $entity): bool
    {
        return $this->canAccessParentEntity($user, $entity);
    }

    /**
     * Authorize edit action
     *
     * @param \App\Model\Entity\User $user User
     * @param \App\Model\Entity\Log $entity Log entity
     * @return bool
     */
    public function canEdit(User $user, Log $entity): bool
    {
        return $this->canAccessParentEntity($user, $entity)
            && ($entity->isNew() || $entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Authorize delete action
     *
     * @param \App\Model\Entity\User $user User
     * @param \App\Model\Entity\Log $entity Log entity
     * @return bool
     */
    public function canDelete(User $user, Log $entity): bool
    {
        return $this->canAccessParentEntity($user, $entity)
            && ($entity->user_id == $user->id || $user->hasRole('admin'));
    }

    /**
     * Check if the user can access the parent entity the log belongs to.
     *
     * @param \App\Model\Entity\User $user User
     * @param \App\Model\Entity\Log $entity Log entity
     * @return bool
     */
    private function canAccessParentEntity(User $user, Log $entity): bool
    {
        // New entities — allow if the user has a foreign_id (target entity)
        if ($entity->isNew()) {
            return !empty($entity->foreign_id);
        }

        $model = (string)($entity->model ?? '');
        $foreignId = (string)($entity->foreign_id ?? '');

        if ($model === '' || $foreignId === '') {
            return false;
        }

        // Project logs
        if ($model === 'Projects.Project') {
            return $this->canAccess($foreignId, $user);
        }

        // For other models, try to load the parent entity and check ownership
        try {
            $parts = explode('.', $model, 2);
            $plugin = $parts[0] ?? null;
            $tableName = $parts[1] ?? $parts[0];

            // Try plural form first (CakePHP convention)
            $alias = $plugin !== null ? "{$plugin}.{$tableName}s" : "{$tableName}s";
            $table = null;

            try {
                $table = TableRegistry::getTableLocator()->get($alias);
            } catch (Throwable) {
                // Try singular form
                $alias = $plugin !== null ? "{$plugin}.{$tableName}" : $tableName;
                $table = TableRegistry::getTableLocator()->get($alias);
            }

            $parentEntity = $table->get($foreignId);

            // Check company ownership
            if (isset($parentEntity->owner_id)) {
                return $parentEntity->owner_id == $user->company_id || $user->hasRole('admin');
            }
            if (isset($parentEntity->user_id)) {
                return $parentEntity->user_id == $user->id || $user->hasRole('admin');
            }

            // No ownership field found — allow if we could load the entity
            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
