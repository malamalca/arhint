<?php
declare(strict_types=1);

namespace LilProjects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsWorkhours Model
 *
 * @property \LilProjects\Model\Table\ProjectsTable|\Cake\ORM\Association\BelongsTo $Projects
 * @property \LilProjects\Model\Table\UsersTable|\Cake\ORM\Association\BelongsTo $Users
 *
 * @method \LilProjects\Model\Entity\ProjectsWorkhour get($primaryKey, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour newEntity($data = null, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour newEmptyEntity(array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour[] newEntities(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour[] patchEntities($entities, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsWorkhour findOrCreate($search, callable $callback = null, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsWorkhoursTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
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
            'className' => 'LilProjects.Projects',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Lil.Users',
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
    public function getTotalDuration($projectId)
    {
        $query = $this->find();
        $data = $query->select(['totalDuration' => $query->func()->sum('duration')])
            ->disableHydration()
            ->first();

        return $data['totalDuration'];
    }
}
