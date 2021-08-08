<?php
declare(strict_types=1);

namespace LilProjects\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ProjectsComposites Model
 *
 * @property \LilProjects\Model\Table\OwnersTable&\Cake\ORM\Association\BelongsTo $Owners
 *
 * @method \LilProjects\Model\Entity\ProjectsComposite newEmptyEntity()
 * @method \LilProjects\Model\Entity\ProjectsComposite newEntity(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite[] newEntities(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite get($primaryKey, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsComposite[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ProjectsCompositesTable extends Table
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

        $this->setTable('projects_composites');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'LilProjects.Projects',
        ]);

        $this->hasMany('CompositesMaterials', [
            'foreignKey' => 'composite_id',
            'className' => 'LilProjects.ProjectsCompMaterials',
            'sort' => 'sort_order'
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
            ->scalar('no')
            ->maxLength('no', 50)
            ->allowEmptyString('no');

        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->allowEmptyString('title');

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

        return $rules;
    }
}
