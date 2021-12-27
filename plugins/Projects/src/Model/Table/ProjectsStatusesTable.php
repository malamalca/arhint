<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsStatuses Model
 *
 * @property \Projects\Model\Table\ProjectsTable&\Cake\ORM\Association\HasMany $Projects
 * @method \Projects\Model\Entity\ProjectsStatus newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsStatus newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[] newEntities(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus get($primaryKey, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \Projects\Model\Entity\ProjectsStatus[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ProjectsStatusesTable extends Table
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

        $this->setTable('projects_statuses');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->hasMany('Projects', [
            'foreignKey' => 'status_id',
            'className' => 'Projects.Projects',
        ]);

        $this->belongsTo('Companies', [
            'foreignKey' => 'owner_id',
            'className' => 'Crm.Contacts',
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

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->notEmptyString('title');

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
        $rules->add($rules->existsIn(['owner_id'], 'Companies'));

        $rules->addDelete(function ($entity, $options) {
            $projectsCount = $this->Projects->find()
                ->where(['Projects.status_id' => $entity->id])
                ->count();

            return $projectsCount == 0;
        }, 'usedInProject');

        return $rules;
    }
}
