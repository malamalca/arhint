<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\I18n\Date;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTimeInterface;
use Projects\Lib\AuthorityWorkflow;

class ProjectsAuthorityEventsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('projects_authority_events');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Cases', ['className' => 'Projects.ProjectsAuthorityCases', 'foreignKey' => 'case_id']);
        $this->belongsTo('Users', ['className' => 'App.Users']);
        $this->belongsTo('Parents', ['className' => 'Projects.ProjectsAuthorityEvents', 'foreignKey' => 'parent_id']);
        $this->hasMany('EventDocuments', [
            'className' => 'Projects.ProjectsAuthorityEventDocuments',
            'foreignKey' => 'event_id',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator->uuid('case_id')->notEmptyString('case_id')
            ->uuid('user_id')->notEmptyString('user_id')
            ->inList('kind', array_keys(AuthorityWorkflow::kinds()))
            ->requirePresence('kind', 'create')->notEmptyString('kind')
            ->date('event_date')->requirePresence('event_date', 'create')->notEmptyDate('event_date')
            ->uuid('parent_id')->allowEmptyString('parent_id')
            ->date('date_due')->allowEmptyDate('date_due')
            ->date('date_valid')->allowEmptyDate('date_valid')
            ->scalar('descript')->allowEmptyString('descript');
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['case_id'], 'Cases'));
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->add(function ($entity) {
            if (!$entity->parent_id) {
                return !in_array($entity->kind, ['supplement', 'void'], true);
            }
            $parent = $this->Parents->find()
                ->where(['id' => $entity->parent_id, 'case_id' => $entity->case_id])->first();
            if (!$parent || $parent->sequence >= $entity->sequence) {
                return false;
            }

            return AuthorityWorkflow::canFollow($entity->kind, $parent->kind);
        }, 'validParent', ['errorField' => 'parent_id']);
        $rules->add(function ($entity) {
            return !$entity->date_due || self::day($entity->date_due) >= self::day($entity->event_date);
        }, 'dueAfterEvent', ['errorField' => 'date_due', 'message' => __d(
            'projects',
            'The due date cannot be before the event date.',
        )]);
        $rules->add(function ($entity) {
            return !$entity->date_valid || self::day($entity->date_valid) >= self::day($entity->event_date);
        }, 'validAfterEvent', ['errorField' => 'date_valid', 'message' => __d(
            'projects',
            'The validity date cannot be before the event date.',
        )]);
        $rules->add(function ($entity) {
            if (!$entity->parent_id) {
                return true;
            }
            $parent = $this->Parents->find()
                ->where(['id' => $entity->parent_id, 'case_id' => $entity->case_id])->first();

            return !$parent || self::day($entity->event_date) >= self::day($parent->event_date);
        }, 'afterParent', ['errorField' => 'event_date', 'message' => __d(
            'projects',
            'The event cannot be dated before the related event.',
        )]);
        $rules->addUpdate(function ($entity) {
            foreach ($this->find()->where(['parent_id' => $entity->id])->all() as $child) {
                if (!AuthorityWorkflow::canFollow($child->kind, $entity->kind)) {
                    return false;
                }
            }

            return true;
        }, 'compatibleChildren', ['errorField' => 'kind', 'message' => __d(
            'projects',
            'This event type is required by a linked event.',
        )]);
        // The history stays consistent: an event that others refer to cannot disappear.
        $rules->addDelete(function ($entity) {
            return !$this->exists(['parent_id' => $entity->id]);
        }, 'hasRelatedEvents', ['errorField' => 'id', 'message' => __d(
            'projects',
            'An event with related events cannot be deleted. Delete the related events first.',
        )]);

        return $rules;
    }

    /**
     * @param \Cake\I18n\Date|\DateTimeInterface|string|null $value Date value.
     * @return string
     */
    private static function day(Date|DateTimeInterface|string|null $value): string
    {
        return $value instanceof Date || $value instanceof DateTimeInterface ?
            $value->format('Y-m-d') : substr((string)$value, 0, 10);
    }
}
