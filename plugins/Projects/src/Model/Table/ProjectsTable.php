<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Projects\Model\Entity\Project;

/**
 * Projects Model
 *
 * @property \Projects\Model\Table\ProjectsLogsTable|\Cake\ORM\Association\HasMany $ProjectsLogs
 * @property \Projects\Model\Table\ProjectsStatusesTable|\Cake\ORM\Association\HasMany $ProjectsStatuses
 * @method \Projects\Model\Entity\Project get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\Project newEntity($data = null, array $options = [])
 * @method \Projects\Model\Entity\Project newEmptyEntity(array $options = [])
 * @method \Projects\Model\Entity\Project[] newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\Project|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\Project patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\Project[] patchEntities($entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\Project findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('ProjectsWorkhours', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.ProjectsWorkhours',
        ]);

        $this->belongsTo('ProjectsStatuses', [
            'foreignKey' => 'status_id',
            'className' => 'Projects.ProjectsStatuses',
        ]);

        $this->hasMany('ProjectsLogs', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.ProjectsLogs',
        ]);

        $this->belongsToMany('Users', [
            'joinTable' => 'projects_users',
        ]);

        $this->hasOne('LastLog', [
            'foreignKey' => false,
            'className' => 'Projects.ProjectsLogs',
            'conditions' => function (QueryExpression $exp, SelectQuery $query) {
                $subquery = clone $query;
                $subquery->select(['SubLastLog.id'])
                    ->from(['SubLastLog' => 'projects_logs'])
                    ->where(['Projects.id = SubLastLog.project_id'])
                    ->order(['SubLastLog.created' => 'DESC'])
                    ->limit(1);

                return $exp->add(['LastLog.id' => $subquery]);
            },
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->uuid('id')
            ->allowEmptyString('id', 'create');

        $validator
            ->maxLength('no', 50)
            ->notEmptyString('no');

        $validator
            ->maxLength('title', 250)
            ->notEmptyString('title');

        $validator
            ->allowEmptyString('ico')
            ->uploadedFile('ico', ['types' => ['image/png'], 'maxSize' => 1000000, 'optional' => true]);

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->addDelete(function ($entity, $options) {
            /** @var \Projects\Model\Table\ProjectsWorkhoursTable $ProjectsWorkhours */
            $ProjectsWorkhours = TableRegistry::getTableLocator()->get('Projects.ProjectsWorkhours');
            $projectsCount = $ProjectsWorkhours->find()
                ->where(['ProjectsWorkhours.project_id' => $entity->id])
                ->count();

            return $projectsCount == 0;
        }, 'usedInProjectsWorkhours');

        $rules->add($rules->isUnique(['owner_id', 'no']), 'uniqueNumber', ['errorField' => 'no']);

        return $rules;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string|null $entityId Entity Id.
     * @param string|null $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(?string $entityId, ?string $ownerId): bool
    {
        return !empty($entityId) && !empty($ownerId) && $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * filter method
     *
     * @param array<string, mixed> $filter Filter data.
     * @return array<string, mixed>
     */
    public function filter(array &$filter): array
    {
        $ret = ['conditions' => [], 'contain' => []];

        if (!empty($filter['inactive'])) {
            $ret['conditions']['Projects.active IN'] = [true, false];
        }

        if (!empty($filter['status'])) {
            $ret['conditions']['Projects.status_id'] = $filter['status'];
        }

        // manual search
        if (!empty($filter['search']) && ($filter['search'] != '[[search]]')) {
            $ret['conditions'][] = ['OR' => [
                'Projects.no LIKE' => '%' . $filter['search'] . '%',
                'Projects.title LIKE' => '%' . $filter['search'] . '%',
            ]];
        }

        if (isset($filter['sort'])) {
            $ret['order'] = [];
        } else {
            $ret['order'] = $filter['order'] ?? [];
        }

        return $ret;
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Projects\Model\Entity\Project $project Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterSave(Event $event, Project $project, ArrayObject $options): void
    {
        Cache::delete('Projects.projectsList.' . $project->owner_id);
    }

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Projects\Model\Entity\Project $project Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, Project $project, ArrayObject $options): void
    {
        Cache::delete('Projects.projectsList.' . $project->owner_id);
    }

    /**
     * List projects by kind for specified owner.
     *
     * @param string|null $ownerId Users Company Id.
     * @param \Cake\ORM\Query\SelectQuery $query Query object.
     * @return array<\Projects\Model\Entity\Project>
     */
    public function findForOwner(?string $ownerId, ?SelectQuery $query = null): array
    {
        if (empty($query)) {
            $query = $this->find();
        }

        if (empty($ownerId)) {
            return [];
        }

        $data = Cache::remember(
            'Projects.projectsList.' . $ownerId,
            function () use ($query) {
                return $query
                    ->where(['active' => true])
                    ->order(['no DESC', 'title'])
                    ->all()
                    ->combine('id', function ($entity) {
                        return $entity;
                    })
                    ->toArray();
            }
        );

        return $data;
    }
}
