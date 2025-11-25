<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsTasks Model
 *
 * @property \Projects\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \Projects\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \Projects\Model\Entity\ProjectsTask newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsTask newEntity(array $data, array $options = [])
 * @method array<\Projects\Model\Entity\ProjectsTask> newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsTask get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsTask findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \Projects\Model\Entity\ProjectsTask patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method array<\Projects\Model\Entity\ProjectsTask> patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsTask|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \Projects\Model\Entity\ProjectsTask saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsTask>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsTask>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsTask>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsTask> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsTask>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsTask>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\Projects\Model\Entity\ProjectsTask>|\Cake\Datasource\ResultSetInterface<\Projects\Model\Entity\ProjectsTask> deleteManyOrFail(iterable $entities, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsTasksTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects_tasks');
        $this->setDisplayField('title');
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
        $this->belongsTo('Milestones', [
            'foreignKey' => 'milestone_id',
            'className' => 'Projects.ProjectsMilestones',
        ]);

        $this->addBehavior('CounterCache', [
            'Milestones' => [
                'tasks_done' => [
                    'conditions' => ['ProjectsTasks.date_complete IS NOT' => null],
                ],
                'tasks_open' => [
                    'conditions' => ['ProjectsTasks.date_complete IS' => null],
                ],
            ],
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
            ->uuid('project_id')
            ->allowEmptyString('project_id');

        $validator
            ->uuid('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->integer('no')
            ->notEmptyString('no');

        $validator
            ->scalar('title')
            ->allowEmptyString('title');

        $validator
            ->scalar('descript')
            ->maxLength('descript', 16777215)
            ->allowEmptyString('descript');

        $validator
            ->date('date_complete')
            ->allowEmptyDate('date_complete');

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
        $rules->add($rules->existsIn(['project_id'], 'Projects'), ['errorField' => 'project_id']);
        $rules->add($rules->existsIn(['user_id'], 'Users'), ['errorField' => 'user_id']);

        return $rules;
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
            $ret['conditions'] = ['ProjectsTasks.id' => $filter['id']];
        }

        if (!empty($filter['project'])) {
            $ret['conditions'][]['ProjectsTasks.project_id IN'] = (array)$filter['project'];
        }

        if (!empty($filter['milestone'])) {
            $ret['conditions'][]['ProjectsTasks.milestone_id IN'] = (array)$filter['milestone'];
        }

        if (!empty($filter['user'])) {
            $ret['conditions'][]['ProjectsTasks.user_id IN'] = (array)$filter['user'];
        }

        $ret['contain'] = [];

        if (isset($filter['sort'])) {
            $ret['order'] = [];
        } else {
            $ret['order'] = $filter['order'] ?? [];
        }

        return $ret;
    }
}
