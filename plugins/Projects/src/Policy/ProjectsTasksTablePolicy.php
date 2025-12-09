<?php
declare(strict_types=1);

namespace Projects\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * ProjectsTasksTable Policy Resolver
 */
class ProjectsTasksTablePolicy
{
    /**
     * Index scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query\SelectQuery $query Query object
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        if (!$user->hasRole('admin')) {
            /** @var \Projects\Model\Table\ProjectsUsersTable $ProjectsUsersTable */
            $ProjectsUsersTable = TableRegistry::getTableLocator()->get('Projects.ProjectsUsers');

            $projectsList = $ProjectsUsersTable->find()
                ->where(['user_id' => $user->id])
                ->all()
                ->combine('project_id', 'user_id')
                ->toArray();

            if (empty($projectsList)) {
                $conditions['ProjectsTasks.id IS'] = null;
            } else {
                $conditions['ProjectsTasks.project_id IN'] = array_keys($projectsList);
            }

            return $query->where($conditions);
        }

        return $query;
    }
}
