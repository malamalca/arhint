<?php
declare(strict_types=1);

namespace Projects\Policy;

use Cake\ORM\TableRegistry;

/**
 * ProjectsWorkhoursTable Policy Resolver
 */
class ProjectsWorkhoursTablePolicy
{
    /**
     * Index scope
     *
     * @param \App\Model\Entity\User $user User
     * @param \Cake\ORM\Query $query Query object
     * @return \Cake\ORM\Query
     */
    public function scopeIndex($user, $query)
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
                $conditions['ProjectsWorkhours.id IS'] = null;
            } else {
                $conditions['ProjectsWorkhours.project_id IN'] = array_keys($projectsList);
            }

            return $query->where($conditions);
        }

        return $query;
    }
}
