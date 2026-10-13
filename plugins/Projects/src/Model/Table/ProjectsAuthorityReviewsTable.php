<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProjectsAuthorityReviewsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('projects_authority_reviews');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Projects', ['className' => 'Projects.Projects']);
        $this->belongsTo('Users', ['className' => 'App.Users']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator->uuid('project_id')->notEmptyString('project_id')
            ->uuid('user_id')->notEmptyString('user_id')
            ->inList('kind', ['ready', 'revoke'])
            ->maxLength('snapshot_hash', 64)->notEmptyString('snapshot_hash');
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['project_id'], 'Projects'));
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->addUpdate(fn() => false, 'appendOnly');
        $rules->addDelete(fn() => false, 'appendOnly');

        return $rules;
    }
}
