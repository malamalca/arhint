<?php
declare(strict_types=1);

namespace LilProjects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Projects Model
 *
 * @property \LilProjects\Model\Table\ProjectsLogsTable|\Cake\ORM\Association\HasMany $ProjectsLogs
 * @property \LilProjects\Model\Table\ProjectsStatusesTable|\Cake\ORM\Association\HasMany $ProjectsStatuses
 * @method \LilProjects\Model\Entity\Project get($primaryKey, $options = [])
 * @method \LilProjects\Model\Entity\Project newEntity($data = null, array $options = [])
 * @method \LilProjects\Model\Entity\Project newEmptyEntity(array $options = [])
 * @method \LilProjects\Model\Entity\Project[] newEntities(array $data, array $options = [])
 * @method \LilProjects\Model\Entity\Project|bool save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \LilProjects\Model\Entity\Project patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\Project[] patchEntities($entities, array $data, array $options = [])
 * @method \LilProjects\Model\Entity\Project findOrCreate($search, callable $callback = null, $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsTable extends Table
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

        $this->setTable('projects');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('ProjectsWorkhours', [
            'foreignKey' => 'project_id',
            'className' => 'LilProjects.ProjectsWorkhours',
        ]);

        $this->belongsTo('ProjectsStatuses', [
            'foreignKey' => 'status_id',
            'className' => 'LilProjects.ProjectsStatuses',
        ]);

        $this->hasMany('ProjectsLogs', [
            'foreignKey' => 'project_id',
            'className' => 'LilProjects.ProjectsLogs',
        ]);

        $this->hasOne('LastLog', [
            'foreignKey' => false,
            'className' => 'LilProjects.ProjectsLogs',
            'conditions' => function (\Cake\Database\Expression\QueryExpression $exp, \Cake\ORM\Query $query) {
                $subquery = $query
                    ->getConnection()
                    ->newQuery()
                    ->select(['SubLastLog.id'])
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
            /** @var \LilProjects\Model\Table\ProjectsWorkhoursTable $ProjectsWorkhours */
            $ProjectsWorkhours = TableRegistry::getTableLocator()->get('LilProjects.ProjectsWorkhours');
            $projectsCount = $ProjectsWorkhours->find()
                ->where(['ProjectsWorkhours.project_id' => $entity->id])
                ->count();

            return $projectsCount == 0;
        }, 'usedInProjectsWorkhours');

        return $rules;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entityId, $ownerId)
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * filter method
     *
     * @param array $filter Filter data.
     * @return array
     */
    public function filter(&$filter)
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
     * List projects by kind for specified owner.
     *
     * @param string $ownerId User Id.
     * @return array
     */
    public function findForOwner($ownerId)
    {
        // In a controller or table method.
        $query = $this->find('list', ['keyField' => 'id', 'valueField' => 'title'])
            ->where(['owner_id' => $ownerId, 'active' => true])
            ->order('title');

        $data = $query->toArray();

        return $data;
    }
}
