<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ProjectsAuthorityEventDocumentsTable extends Table
{
    /**
     * @param array<string, mixed> $config Table configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('projects_authority_event_documents');
        $this->belongsTo('Events', ['className' => 'Projects.ProjectsAuthorityEvents', 'foreignKey' => 'event_id']);
        $this->belongsTo('Documents', ['className' => 'Documents.Documents']);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        return $validator->uuid('event_id')->notEmptyString('event_id')
            ->uuid('document_id')->notEmptyString('document_id')
            ->inList('role', ['primary', 'evidence', 'documentation']);
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules Rules checker.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['event_id'], 'Events'));
        $rules->add($rules->existsIn(['document_id'], 'Documents'));
        $rules->add(function ($entity) {
            $event = $this->Events->get($entity->event_id, contain: ['Cases' => ['Projects']]);
            $document = $this->Documents->get($entity->document_id);

            return $document->project_id === $event->case->project_id &&
                $document->owner_id === $event->case->project->owner_id;
        }, 'projectDocument', ['errorField' => 'document_id']);
        // Links are replaced only by the audited event transaction, never edited in place.
        $rules->addUpdate(fn() => false, 'appendOnly');

        return $rules;
    }
}
