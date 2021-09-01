<?php
declare(strict_types=1);

namespace LilProjects\Model\Table;

use ArrayObject;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use LilProjects\Model\Entity\ProjectsCompMaterial;

/**
 * ProjectsCompMaterials Model
 *
 * @property \LilProjects\Model\Table\CompositesTable&\Cake\ORM\Association\BelongsTo $Composites
 *
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial newEmptyEntity()
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial newEntity(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[] newEntities(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial get($primaryKey, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \LilProjects\Model\Entity\ProjectsCompMaterial[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ProjectsCompMaterialsTable extends Table
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

        $this->setTable('projects_comp_materials');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->belongsTo('Composites', [
            'foreignKey' => 'composite_id',
            'className' => 'LilProjects.ProjectsComposites',
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
            ->scalar('descript')
            ->maxLength('descript', 255)
            ->notEmptyString('descript');

        $validator
            ->decimal('thickness')
            ->allowEmptyString('thickness');

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
        $rules->add($rules->existsIn(['composite_id'], 'Composites'), ['errorField' => 'composite_id']);

        return $rules;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilProjects\Model\Entity\ProjectsCompMaterial $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function beforeSave(Event $event, ProjectsCompMaterial $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $res = $this->find()
                ->select(['max_order' => new QueryExpression('MAX(sort_order)')])
                ->where(['composite_id' => $entity->composite_id])
                ->enableHydration(false)
                ->first();

            $entity->sort_order = $res['max_order'] + 1;
        
        }
 	}

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilProjects\Model\Entity\ProjectsCompMaterial $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, ProjectsCompMaterial $entity, ArrayObject $options)
    {
		$this->updateAll(
			[new QueryExpression('sort_order = sort_order - 1')],
			[
				'composite_id' => $entity->composite_id,
				'sort_order >' => $entity->sort_order,
			]
		);
 	}

    /**
     * reorder method
     *
     * @param \LilProjects\Model\Entity\ProjectsCompMaterial $material 
     * @param int $newPosition New position inside section
     * @return bool
     */
	public function reorder($material, $newPosition) {

        $qExpr = new QueryExpression();

        if ($material->sort_order < $newPosition) {
            $delta = '-1'; // moving down
            $qExpr->between('sort_order', $material->sort_order, $newPosition);
        } else {
            $delta = '+1'; // moving up        
            $qExpr->between('sort_order', $newPosition, $material->sort_order);
        }
        
        // update all for new
        $this->updateAll(
            [new QueryExpression('sort_order = sort_order ' . $delta)],
            ['composite_id' => $material->composite_id, $qExpr]
        );
        
        // update sorted item
        $this->updateAll(['sort_order' => $newPosition], ['id' => $material->id]);
        
        return true;
	}
}
