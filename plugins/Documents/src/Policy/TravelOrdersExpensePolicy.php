<?php
declare(strict_types=1);

namespace Documents\Policy;

use App\Model\Entity\User;
use Cake\ORM\TableRegistry;
use Documents\Model\Entity\TravelOrder;
use Documents\Model\Entity\TravelOrdersExpense;

/**
 * TravelOrdersExpense Policy Resolver
 */
class TravelOrdersExpensePolicy
{
    /**
     * Load the parent TravelOrder for a given expense.
     *
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @return \Documents\Model\Entity\TravelOrder|null
     */
    private function parentOrder(TravelOrdersExpense $entity): ?TravelOrder
    {
        if (empty($entity->travel_order_id)) {
            return null;
        }

        /** @var \Documents\Model\Table\TravelOrdersTable $table */
        $table = TableRegistry::getTableLocator()->get('Documents.TravelOrders');

        /** @var \Documents\Model\Entity\TravelOrder|null $order */
        $order = $table->find()
            ->select(['id', 'owner_id', 'status', 'entered_by_id'])
            ->where(['id' => $entity->travel_order_id])
            ->first();

        return $order;
    }

    /**
     * Authorize edit action.
     *
     * Admin: allowed when the order is approved or in waiting-processing.
     * Author: allowed only when the order is in approved status.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @return bool
     */
    public function canEdit(User $user, TravelOrdersExpense $entity): bool
    {
        $order = $this->parentOrder($entity);
        if ($order === null || $order->owner_id !== $user->company_id) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return in_array($order->status, [
                TravelOrder::STATUS_APPROVED,
                TravelOrder::STATUS_WAITING_PROCESSING,
            ], true);
        }

        return $user->hasRole('editor')
            && $order->entered_by_id !== null
            && $order->entered_by_id === $user->id
            && $order->status === TravelOrder::STATUS_APPROVED;
    }

    /**
     * Authorize delete action.
     *
     * Same rules as edit.
     *
     * @param \App\Model\Entity\User $user User
     * @param \Documents\Model\Entity\TravelOrdersExpense $entity Entity
     * @return bool
     */
    public function canDelete(User $user, TravelOrdersExpense $entity): bool
    {
        return $this->canEdit($user, $entity);
    }
}
