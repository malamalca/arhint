<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use App\Model\Entity\User;
use ArrayObject;
use Cake\Database\Query\SelectQuery;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Projects\Filter\ProjectsTasksFilter;

/**
 * ProjectsTasks Model
 *
 * @property \Projects\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \Projects\Model\Table\ProjectsMilestonesTable&\Cake\ORM\Association\BelongsTo $Milestones
 * @property \Projects\Model\Table\ProjectsTasksCommentsTable&\Cake\ORM\Association\HasMany $Comments
 * @method \Projects\Model\Entity\ProjectsTask newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsTask newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsTask get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsTask patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsTask|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
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
        $this->hasMany('Comments', [
            'foreignKey' => 'task_id',
            'className' => 'Projects.ProjectsTasksComments',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'sort' => ['Comments.created' => 'ASC'],
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
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Crm\Model\Entity\ContactsAccount $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        /** @var \Projects\Model\Entity\ProjectsTask $entity */
        if ($entity->isNew()) {
            $maxNo = $this->find()
                ->where(['project_id' => $entity->project_id])
                ->select(['max_no' => $this->find()->func()->max('no')])
                ->first()
                ->get('max_no');
            $entity->no = $maxNo + 1;
        }
    }

    /**
     * Find tasks count
     *
     * @param \Cake\Database\Query\SelectQuery<mixed> $query Query object.
     * @param string $projectId Project id.
     * @param \App\Model\Entity\User $currentUser Current user.
     * @param \Projects\Filter\ProjectsTasksFilter $filter Filter object.
     * @return \Cake\Database\Query\SelectQuery<mixed>
     */
    public function findTasksCount(
        SelectQuery $query,
        string $projectId,
        User $currentUser,
        ProjectsTasksFilter $filter,
    ): SelectQuery {
        $filter->delete('status');

        return $query
            ->select([
                'open' => $query->func()->count(
                    $query->newExpr()->case()
                        ->when(['date_complete IS' => null])
                        ->then(1),
                ),
                'closed' => $query->func()->count(
                    $query->newExpr()->case()
                        ->when(['date_complete IS NOT' => null])
                        ->then(1),
                ),
            ])
            ->where(['project_id' => $projectId])
            ->where($filter->getParams($projectId, $currentUser)['conditions']);
    }
}
