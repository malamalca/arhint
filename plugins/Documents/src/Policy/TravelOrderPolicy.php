<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Documents\Model\Entity\TravelOrder;

/**
 * TravelOrder Policy Resolver
 */
class TravelOrderPolicy
{
    /**
     * Returns true when the user is the original author of the travel order.
     */
    private function isAuthor(User $user, TravelOrder $entity): bool
    {
        return $entity->entered_by_id !== null && $entity->entered_by_id === $user->id;
    }

    /**
     * Authorize view action.
     *
     * Admin sees all orders within the company.
     * Ordinal users see only their own orders.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canView(User $user, TravelOrder $entity): bool
    {
        if ($entity->owner_id !== $user->company_id) {
            return false;
        }

        return $user->hasRole('admin') || $this->isAuthor($user, $entity);
    }

    /**
     * Authorize edit action.
     *
     * Admin: editable in any status except completed.
     * Author: editable only when status is draft or declined.
     * Processing/waiting-processing phase locks out ordinal users.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canEdit(User $user, TravelOrder $entity): bool
    {
        if ($entity->owner_id !== $user->company_id) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return $entity->status !== TravelOrder::STATUS_COMPLETED;
        }

        return $user->hasRole('editor')
            && $this->isAuthor($user, $entity)
            && in_array($entity->status, [
                TravelOrder::STATUS_DRAFT,
                TravelOrder::STATUS_DECLINED,
            ], true);
    }

    /**
     * Authorize email action.
     *
     * Admin or original author only.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canEmail(User $user, TravelOrder $entity): bool
    {
        if ($entity->owner_id !== $user->company_id) {
            return false;
        }

        return $user->hasRole('admin') || $this->isAuthor($user, $entity);
    }

    /**
     * Authorize sign action.
     *
     * Signs the draft, sending it for approval. Allowed for the author
     * (or admin) when the order is still in draft status.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canSign(User $user, TravelOrder $entity): bool
    {
        if ($entity->owner_id !== $user->company_id) {
            return false;
        }

        if ($entity->status !== TravelOrder::STATUS_DRAFT) {
            return false;
        }

        return $user->hasRole('admin') || ($user->hasRole('editor') && $this->isAuthor($user, $entity));
    }

    /**
     * Authorize delete action.
     *
     * Admin can delete at any non-completed status.
     * Author can only delete their own order when it is still in draft.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canDelete(User $user, TravelOrder $entity): bool
    {
        if ($entity->owner_id !== $user->company_id) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return $entity->status !== TravelOrder::STATUS_COMPLETED;
        }

        return $user->hasRole('editor')
            && $this->isAuthor($user, $entity)
            && $entity->status === TravelOrder::STATUS_DRAFT;
    }

    /**
     * Authorize approve action (admin only).
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canApprove(User $user, TravelOrder $entity): bool
    {
        return $entity->owner_id === $user->company_id && $user->hasRole('admin');
    }

    /**
     * Authorize submit action.
     *
     * Submits an approved order for processing. Allowed for the
     * original author (or admin) when status is approved.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canSubmit(User $user, TravelOrder $entity): bool
    {
        if ($entity->owner_id !== $user->company_id) {
            return false;
        }

        if ($entity->status !== TravelOrder::STATUS_APPROVED) {
            return false;
        }

        return $user->hasRole('admin') || ($user->hasRole('editor') && $this->isAuthor($user, $entity));
    }

    /**
     * Authorize process action (admin only).
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canProcess(User $user, TravelOrder $entity): bool
    {
        return $entity->owner_id === $user->company_id && $user->hasRole('admin');
    }

    /**
     * Authorize decline action (admin only).
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrder $entity Entity
     * @return bool
     */
    public function canDecline(User $user, TravelOrder $entity): bool
    {
        return $entity->owner_id === $user->company_id && $user->hasRole('admin');
    }
}
