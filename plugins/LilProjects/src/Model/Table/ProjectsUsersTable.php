<?php
declare(strict_types=1);

namespace LilProjects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsUsers Model
 *
 * @property \LilProjects\Model\Table\ProjectsTable&\Cake\ORM\Association\BelongsTo $Projects
 * @property \LilProjects\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @method \LilProjects\Model\Entity\ProjectsUser newEmptyEntity()
 * @method \LilProjects\Model\Entity\ProjectsUser newEntity(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser[] newEntities(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser get($primaryKey, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsUser[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ProjectsUsersTable extends Table
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

        $this->setTable('projects_users');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'LilProjects.Projects',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'LilProjects.Users',
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
            ->allowEmptyString('id', null, 'create');

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
}
