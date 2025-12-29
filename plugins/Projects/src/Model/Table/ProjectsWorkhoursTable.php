<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use App\Model\Entity\User;
use Cake\Database\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Projects\Filter\ProjectsWorkhoursFilter;

/**
 * ProjectsWorkhours Model
 *
 * @property \Projects\Model\Table\ProjectsTable|\Cake\ORM\Association\BelongsTo $Projects
 * @property \Projects\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 * @method \Projects\Model\Entity\ProjectsWorkhour get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsWorkhour newEntity($data = null, array $options = [])
 * @method \Projects\Model\Entity\ProjectsWorkhour newEmptyEntity(array $options = [])
 * @method \Projects\Model\Entity\ProjectsWorkhour[] newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsWorkhour|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsWorkhour patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsWorkhour[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsWorkhour findOrCreate($search, array<array-key, mixed>|callable|null $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsWorkhoursTable extends Table
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

        $this->setTable('projects_workhours');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.Projects',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'App.Users',
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
            ->dateTime('started')
            ->allowEmptyString('started');

        $validator
            ->notEmptyString('descript');

        $validator
            ->integer('duration');

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
        $rules->add($rules->existsIn(['project_id'], 'Projects'));
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    /**
     * Returns total duration for specified project id.
     *
     * @param string $projectId Project id
     * @return int Duration in seconds
     */
    public function getTotalDuration(string $projectId): int
    {
        $query = $this->find();
        $data = $query->select(['totalDuration' => $query->func()->sum('duration')])
            ->where(['project_id' => $projectId])
            ->disableHydration()
            ->first();

        return (int)$data['totalDuration'];
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

        if (!empty($filter['id'])) {
            $ret['conditions'] = ['ProjectsWorkhours.id' => $filter['id']];
        }

        if (!empty($filter['project'])) {
            $ret['conditions'][]['ProjectsWorkhours.project_id IN'] = (array)$filter['project'];
        }

        if (!empty($filter['user'])) {
            $ret['conditions'][]['ProjectsWorkhours.user_id IN'] = (array)$filter['user'];
        }

        $ret['contain'] = [];

        if (isset($filter['sort'])) {
            $ret['order'] = [];
        } else {
            $ret['order'] = $filter['order'] ?? [];
        }

        if (isset($filter['limit'])) {
            $ret['limit'] = $filter['limit'];
        } else {
            $ret['limit'] = null;
        }

        return $ret;
    }

    /**
     * Find workhours count
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query Query object.
     * @param string $projectId Project id.
     * @param \App\Model\Entity\User $currentUser Current user.
     * @param \Projects\Filter\ProjectsWorkhoursFilter $filter Filter object.
     * @return \Cake\Database\Query\SelectQuery<mixed>
     */
    public function findWorkhoursCount(
        SelectQuery $query,
        User $currentUser,
        ProjectsWorkhoursFilter $filter,
    ): SelectQuery {
        $filter->delete('status');

        return $query
            ->select([
                'open' => $query->func()->count(
                    $query->newExpr()->case()
                        ->when(['dat_confirmed IS' => null])
                        ->then(1),
                ),
                'closed' => $query->func()->count(
                    $query->newExpr()->case()
                        ->when(['dat_confirmed IS NOT' => null])
                        ->then(1),
                ),
            ])
            ->where($filter->getParams($currentUser)['conditions']);
    }
}
