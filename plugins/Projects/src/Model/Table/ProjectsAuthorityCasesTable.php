<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ProjectsAuthorityCasesTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('projects_authority_cases');
        $this->setDisplayField('title');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Projects', ['className' => 'Projects.Projects']);
        $this->belongsTo('Authorities', [
            'className' => 'Crm.Contacts', 'foreignKey' => 'contact_id', 'strategy' => 'select',
        ]);
        $this->belongsTo('Users', ['className' => 'App.Users']);
        $this->hasMany('Events', [
            'className' => 'Projects.ProjectsAuthorityEvents',
            'foreignKey' => 'case_id',
            'sort' => ['Events.sequence' => 'ASC'],
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator->uuid('project_id')->notEmptyString('project_id')
            ->uuid('contact_id')->requirePresence('contact_id', 'create')->notEmptyString('contact_id')
            ->scalar('title')->maxLength('title', 250)->requirePresence('title', 'create')->notEmptyString('title')
            ->uuid('user_id')->allowEmptyString('user_id')
            ->scalar('reference')->maxLength('reference', 100)->allowEmptyString('reference')
            ->scalar('descript')->allowEmptyString('descript')
            ->scalar('exempt_reason')->allowEmptyString('exempt_reason');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['project_id'], 'Projects'));
        $rules->add($rules->existsIn(['contact_id'], 'Authorities'));
        $rules->add(function ($entity) {
            $project = $this->Projects->get($entity->project_id);
            $authority = $this->Authorities->find()->where(['id' => $entity->contact_id])->first();

            return $authority && $authority->kind === 'C' && $project->owner_id === $authority->owner_id;
        }, 'sameCompany', ['errorField' => 'contact_id']);
        $rules->add(function ($entity) {
            if (!$entity->user_id) {
                return true;
            }
            $user = $this->Users->find()->where(['id' => $entity->user_id])->first();
            if (!$user || $user->company_id !== $this->Projects->get($entity->project_id)->owner_id) {
                return false;
            }

            return $user->hasRole('admin') || TableRegistry::getTableLocator()->get('Projects.ProjectsUsers')
                ->exists(['project_id' => $entity->project_id, 'user_id' => $entity->user_id]);
        }, 'projectMember', ['errorField' => 'user_id']);

        return $rules;
    }
}
