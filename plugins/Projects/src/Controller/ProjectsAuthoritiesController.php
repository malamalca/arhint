<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Database\Driver\Sqlite;
use Cake\Datasource\EntityInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\Date;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Projects\Lib\AuthorityCalendar;
use Projects\Lib\AuthorityWorkflow;
use Projects\Lib\StaleSnapshotException;

/**
 * All procedure writes are authorized against the immutable project ID.
 */
class ProjectsAuthoritiesController extends AppController
{
    /**
     * Project procedures. Authorities are companies from the shared CRM.
     *
     * @return void
     */
    public function index(): void
    {
        $project = $this->project((string)$this->getRequest()->getQuery('project'), 'view');
        $workflow = new AuthorityWorkflow();
        $cases = $workflow->cases($project->id);
        $reviews = $this->fetchTable('Projects.ProjectsAuthorityReviews')->find()
            ->where(['project_id' => $project->id])->contain(['Users'])->orderBy(['sequence'])->all()->toList();
        $summary = $workflow->summary($project, $cases, $reviews);
        $this->set(compact('project', 'cases', 'reviews', 'summary'));
    }

    /**
     * @param string|null $id Case ID.
     * @return \Cake\Http\Response|null
     */
    public function editCase(?string $id = null): ?Response
    {
        $table = $this->fetchTable('Projects.ProjectsAuthorityCases');
        $case = $id ? $table->get($id) : $table->newEmptyEntity();
        $projectId = $id ? $case->project_id : (string)$this->getRequest()->getQuery('project');
        $project = $this->project($projectId, 'authorities');
        $case->project_id = $project->id;
        $summary = $this->summary($project);
        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            $previousContact = $case->contact_id;
            $table->patchEntity($case, $this->getRequest()->getData());
            if (trim((string)$case->exempt_reason) === '') {
                $case->exempt_reason = null;
            }
            // An existing history must never be reassigned to a different authority.
            if (
                $id && $previousContact !== $case->contact_id &&
                $this->fetchTable('Projects.ProjectsAuthorityEvents')->exists(['case_id' => $id])
            ) {
                $case->setError('contact_id', __d('projects', 'Add a new case to change the authority.'));
            }
            $changes = $this->changes($case);
            $isNew = $case->isNew();
            $case->revision = Text::uuid();
            try {
                $table->getConnection()->transactional(
                    function () use ($project, $table, $case, $changes, $isNew): void {
                        $this->lockProject($project->id);
                        $this->checkSnapshot($this->summary($this->fetchTable('Projects.Projects')->get($project->id)));
                        $table->saveOrFail($case);
                        if ($isNew) {
                            $this->logProcedure($project->id, __d(
                                'projects',
                                'Authority case added: {0}',
                                $case->title,
                            ));
                        } elseif ($changes) {
                            $this->logProcedure($project->id, __d(
                                'projects',
                                'Authority case updated: {0}. Changes: {1}',
                                $case->title,
                                $this->describe($changes),
                            ));
                        }
                    },
                );
                $this->Flash->success(__d('projects', 'The authority case has been saved.'));

                return $this->back($project->id);
            } catch (PersistenceFailedException $exception) {
                $this->Flash->error(__d('projects', 'The authority case could not be saved. Check the entered data.'));
            } catch (StaleSnapshotException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }
        $contact = $case->contact_id ? $this->fetchTable('Crm.Contacts')->find()->where([
            'id' => $case->contact_id, 'owner_id' => $project->owner_id, 'kind' => 'C',
        ])->first() : null;
        $contactTitle = $contact->title ?? '';
        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('App.Users');
        $users = $usersTable->fetchForCompany($project->owner_id);
        $members = $this->fetchTable('Projects.ProjectsUsers')->find()->where(['project_id' => $project->id])
            ->all()->extract('user_id')->toArray();
        $responsibleUsers = [];
        foreach ($users as $user) {
            if ($user->hasRole('admin') || in_array($user->id, $members, true)) {
                $responsibleUsers[$user->id] = $user->name;
            }
        }
        $this->set(compact('project', 'case', 'contactTitle', 'responsibleUsers', 'summary'));

        return null;
    }

    /**
     * Delete a case that has no recorded history. POST only.
     *
     * @param string $id Case ID.
     * @return \Cake\Http\Response|null
     */
    public function deleteCase(string $id): ?Response
    {
        $this->getRequest()->allowMethod(['post', 'delete']);
        $table = $this->fetchTable('Projects.ProjectsAuthorityCases');
        $case = $table->get($id);
        $project = $this->project($case->project_id, 'authorities');
        $deleted = false;
        $table->getConnection()->transactional(function () use ($project, $table, $case, &$deleted): void {
            $this->lockProject($project->id);
            if ($this->fetchTable('Projects.ProjectsAuthorityEvents')->exists(['case_id' => $case->id])) {
                return;
            }
            // Personal calendar subscriptions of the removed case remove themselves (AuthorityCalendarEvents).
            $table->deleteOrFail($case);
            $this->logProcedure($project->id, __d('projects', 'Authority case deleted: {0}', $case->title));
            $deleted = true;
        });
        if ($deleted) {
            $this->Flash->success(__d('projects', 'The authority case has been deleted.'));

            return $this->back($project->id);
        }
        $this->Flash->error(__d('projects', 'Only a case without recorded events can be deleted.'));

        return $this->redirect(['action' => 'view', $case->id]);
    }

    /**
     * @param string $id Case ID.
     * @return void
     */
    public function view(string $id): void
    {
        $case = $this->fetchTable('Projects.ProjectsAuthorityCases')->get($id);
        $project = $this->project($case->project_id, 'view');
        $workflow = new AuthorityWorkflow();
        $cases = $workflow->cases($project->id);
        foreach ($cases as $loadedCase) {
            if ($loadedCase->id === $id) {
                $case = $loadedCase;
                break;
            }
        }
        $summary = $this->summary($project, $cases);
        $effectiveIds = array_map(fn($event) => $event->id, $workflow->effectiveEvents($case->events));
        $selectableIds = array_map(fn($event) => $event->id, $workflow->selectableEvents($case->events));
        $calendarLink = $this->fetchTable('Projects.ProjectsAuthorityCalendarLinks')->find()->where([
            'case_id' => $id, 'calendar_id' => $this->getCurrentUser()->id,
        ])->first();
        $this->set(compact('project', 'case', 'summary', 'effectiveIds', 'selectableIds', 'calendarLink'));
    }

    /**
     * Subscribe to deadlines in the current user's personal calendar.
     *
     * @param string $id Case ID.
     * @return \Cake\Http\Response|null
     */
    public function calendar(string $id): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $case = $this->fetchTable('Projects.ProjectsAuthorityCases')->get($id);
        $project = $this->project($case->project_id, 'authorityCalendar');
        $this->fetchTable('Projects.Projects')->getConnection()->transactional(function () use ($id, $project): void {
            $this->lockProject($project->id);
            $calendar = new AuthorityCalendar();
            if ($this->getRequest()->getData('remove') === '1') {
                $calendar->unsubscribe($id, $this->getCurrentUser()->id);
            } else {
                $calendar->subscribe($id, $this->getCurrentUser());
            }
        });
        $this->Flash->success(__d('projects', 'The calendar link has been updated.'));

        return $this->redirect(['action' => 'view', $id]);
    }

    /**
     * Create or edit an event and its document links in a single transaction.
     *
     * @param string $id Case ID.
     * @param string|null $eventId Event ID when editing.
     * @return \Cake\Http\Response|null
     */
    public function event(string $id, ?string $eventId = null): ?Response
    {
        $case = $this->fetchTable('Projects.ProjectsAuthorityCases')->get($id);
        $project = $this->project($case->project_id, 'authorities');
        $workflow = new AuthorityWorkflow();
        $cases = $workflow->cases($project->id);
        foreach ($cases as $loadedCase) {
            if ($loadedCase->id === $id) {
                $case = $loadedCase;
                break;
            }
        }
        $summary = $this->summary($project, $cases);
        $table = $this->fetchTable('Projects.ProjectsAuthorityEvents');
        $event = $eventId ? $table->find()->where(['id' => $eventId, 'case_id' => $id])
            ->contain(['EventDocuments'])->firstOrFail() : $table->newEmptyEntity();
        if ($event->isNew()) {
            $event->event_date = Date::today();
            $event->kind = 'submission';
        } else {
            $effectiveIds = array_map(fn($item) => $item->id, $workflow->effectiveEvents($case->events));
            if (!in_array($event->id, $effectiveIds, true)) {
                throw new BadRequestException(__d('projects', 'Select a valid event from this case.'));
            }
        }
        $existingDocuments = array_map(fn($link) => $link->document_id, $event->event_documents ?? []);
        $selectedDocuments = array_values(array_filter(
            (array)$this->getRequest()->getData('document_ids', $existingDocuments),
            fn($value) => $value !== '' && $value !== null,
        ));
        $documents = $this->documentOptions($project);
        if ($this->getRequest()->is(['post', 'put', 'patch'])) {
            $table->patchEntity($event, $this->getRequest()->getData());
            $changes = $this->changes($event);
            $event->case_id = $case->id;
            if ($event->isNew()) {
                $event->user_id = $this->getCurrentUser()->id;
            }
            try {
                $table->getConnection()->transactional(function () use (
                    $project,
                    $case,
                    $table,
                    $event,
                    $workflow,
                    $selectedDocuments,
                    $existingDocuments,
                    $changes,
                ): void {
                    $this->lockProject($project->id);
                    $freshProject = $this->fetchTable('Projects.Projects')->get($project->id);
                    $this->checkSnapshot($this->summary($freshProject));
                    $editing = !$event->isNew();
                    if (!$editing) {
                        $event->sequence = count($case->events) + 1;
                    }
                    $this->validateEvent($event, $case, $workflow);
                    $allowed = $this->documentOptions($freshProject);
                    $links = [];
                    foreach (array_unique($selectedDocuments) as $documentId) {
                        if (!is_string($documentId) || !isset($allowed[$documentId])) {
                            $event->setError('document_ids', __d(
                                'projects',
                                'Select accessible documents from this project.',
                            ));
                            continue;
                        }
                        $links[] = [$documentId, 'primary'];
                    }
                    if (in_array($event->kind, ['conditions', 'opinion', 'request'], true) && !$selectedDocuments) {
                        $event->setError('document_ids', __d('projects', 'A response document is required.'));
                    }
                    $table->saveOrFail($event);
                    $linkTable = $this->fetchTable('Projects.ProjectsAuthorityEventDocuments');
                    $newIds = array_column($links, 0);
                    // Only differences are written, inside this authorized and audited transaction.
                    $removed = array_values(array_diff($existingDocuments, $newIds));
                    $added = array_values(array_diff($newIds, $existingDocuments));
                    if ($removed) {
                        $linkTable->deleteAll(['event_id' => $event->id, 'document_id IN' => $removed]);
                    }
                    foreach ($added as $documentId) {
                        $link = $linkTable->newEmptyEntity();
                        $link->event_id = $event->id;
                        $link->document_id = $documentId;
                        $link->role = 'primary';
                        $linkTable->saveOrFail($link);
                    }
                    if ($removed || $added) {
                        $changes['document_ids'] = [array_values($existingDocuments), $newIds];
                    }
                    if ($editing) {
                        $this->logProcedure($project->id, __d(
                            'projects',
                            'Procedure event updated: {0}. Changes: {1}',
                            $case->title,
                            $this->describe($changes),
                        ), $case->id);
                    } else {
                        $this->logProcedure($project->id, __d(
                            'projects',
                            '{0}: {1}.',
                            $case->title,
                            AuthorityWorkflow::kinds()[$event->kind],
                        ), $case->id);
                    }
                });
                $this->Flash->success(__d('projects', 'The procedure event has been recorded.'));

                return $this->redirect(['action' => 'view', $case->id]);
            } catch (PersistenceFailedException $exception) {
                $this->Flash->error(__d('projects', 'The event could not be saved. Check the entered data.'));
            } catch (StaleSnapshotException $exception) {
                $this->Flash->error($exception->getMessage());
            }
        }
        $parents = [];
        $parentEvents = [];
        foreach ($workflow->selectableEvents($case->events) as $parent) {
            if ($eventId && $parent->sequence >= $event->sequence) {
                continue;
            }
            $parentEvents[$parent->id] = ['kind' => $parent->kind];
            $parents[$parent->id] = $parent->sequence . '. ' . $parent->event_date . ' · ' .
                AuthorityWorkflow::kinds()[$parent->kind];
        }
        $this->set(compact(
            'project',
            'case',
            'event',
            'summary',
            'documents',
            'parents',
            'parentEvents',
            'selectedDocuments',
        ));

        return null;
    }

    /**
     * Edit an existing procedure event using the same protected form.
     *
     * @param string $id Event ID.
     * @return \Cake\Http\Response|null
     */
    public function editEvent(string $id): ?Response
    {
        $event = $this->fetchTable('Projects.ProjectsAuthorityEvents')->get($id);
        $this->viewBuilder()->setTemplate('event');

        return $this->event($event->case_id, $id);
    }

    /**
     * Append a project review. POST only.
     *
     * @param string $id Project ID.
     * @return \Cake\Http\Response
     */
    public function review(string $id): ?Response
    {
        $this->getRequest()->allowMethod(['post']);
        $project = $this->project($id, 'authorityReview');
        $table = $this->fetchTable('Projects.ProjectsAuthorityReviews');
        $kind = (string)$this->getRequest()->getData('kind');
        if (!in_array($kind, ['ready', 'revoke'], true)) {
            throw new BadRequestException(__d('projects', 'Invalid review action.'));
        }
        try {
            $this->recordReview($project, $table, $kind);
        } catch (StaleSnapshotException $exception) {
            $this->Flash->error($exception->getMessage());

            return $this->back($project->id);
        }
        $this->Flash->success(__d('projects', 'The project review has been recorded.'));

        return $this->back($project->id);
    }

    /**
     * @param \Cake\Datasource\EntityInterface $project Project.
     * @param \Cake\ORM\Table $table Reviews table.
     * @param string $kind Review kind.
     * @return void
     */
    private function recordReview(EntityInterface $project, Table $table, string $kind): void
    {
        $table->getConnection()->transactional(function () use ($project, $table, $kind): void {
            $this->lockProject($project->id);
            $projects = $this->fetchTable('Projects.Projects');
            $freshProject = $projects->get($project->id);
            $summary = $this->summary($freshProject);
            $this->checkSnapshot($summary);
            if ($kind === 'ready' && !$summary['collected']) {
                throw new BadRequestException(__d(
                    'projects',
                    'Resolve the outstanding requirements before confirming.',
                ));
            }
            $review = $table->newEmptyEntity();
            $review->project_id = $project->id;
            $review->sequence = $table->find()->where(['project_id' => $project->id])->count() + 1;
            $review->user_id = $this->getCurrentUser()->id;
            $review->kind = $kind;
            $review->snapshot_hash = $summary['snapshotHash'];
            $table->saveOrFail($review);
            $labels = [
                'ready' => __d('projects', 'Ready for submission to UE'),
                'revoke' => __d('projects', 'Submission readiness revoked'),
            ];
            $this->logProcedure($project->id, $labels[$kind]);
        });
    }

    /**
     * Validate transitions against the current history of the case.
     *
     * @param \Cake\Datasource\EntityInterface $event New event.
     * @param \Cake\Datasource\EntityInterface $case Loaded case.
     * @param \Projects\Lib\AuthorityWorkflow $workflow Workflow.
     * @return void
     */
    private function validateEvent(
        EntityInterface $event,
        EntityInterface $case,
        AuthorityWorkflow $workflow,
    ): void {
        $parent = null;
        foreach ($workflow->selectableEvents($case->events) as $candidate) {
            if ($candidate->id === $event->parent_id) {
                $parent = $candidate;
            }
        }
        if ($event->parent_id && !$parent) {
            $event->setError('parent_id', __d('projects', 'Select a valid event from this case.'));
        }
        if ($parent && !AuthorityWorkflow::canFollow($event->kind, $parent->kind)) {
            $event->setError('parent_id', __d('projects', 'The related event must match the event type.'));
        }
        if (!$event->parent_id && in_array($event->kind, ['supplement', 'void'], true)) {
            $event->setError('parent_id', __d('projects', 'Select a valid event from this case.'));
        }
        if ($event->kind === 'void' && trim((string)$event->descript) === '') {
            $event->setError('descript', __d('projects', 'A reason is required.'));
        }
    }

    /**
     * @param string $id Project ID.
     * @param string $action Policy action.
     * @return \Cake\Datasource\EntityInterface
     */
    private function project(string $id, string $action): EntityInterface
    {
        $project = $this->fetchTable('Projects.Projects')->get($id);
        $this->Authorization->authorize($project, $action);

        return $project;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $project Project.
     * @return array<string, string>
     */
    private function documentOptions(EntityInterface $project): array
    {
        $table = $this->fetchTable('Documents.Documents');
        $query = $this->Authorization->applyScope($table->find(), 'index')
            ->where(['Documents.project_id' => $project->id, 'Documents.owner_id' => $project->owner_id])
            ->orderBy(['Documents.dat_issue' => 'DESC']);
        $documents = [];
        foreach ($query->all() as $document) {
            $documents[$document->id] = trim($document->no . ' ' . $document->title) . ' · ' . $document->dat_issue;
        }

        return $documents;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $project Project.
     * @param array<\Cake\Datasource\EntityInterface>|null $cases Optional loaded cases.
     * @return array<string, mixed>
     */
    private function summary(EntityInterface $project, ?array $cases = null): array
    {
        $workflow = new AuthorityWorkflow();
        $reviews = $this->fetchTable('Projects.ProjectsAuthorityReviews')->find()
            ->where(['project_id' => $project->id])->orderBy(['sequence'])->all()->toList();

        return $workflow->summary($project, $cases ?? $workflow->cases($project->id), $reviews);
    }

    /**
     * Lock the project to serialize all procedure changes and confirmations.
     *
     * @param string $id Project ID.
     * @return void
     */
    private function lockProject(string $id): void
    {
        $table = $this->fetchTable('Projects.Projects');
        $query = $table->find()->where(['id' => $id]);
        if (!$table->getConnection()->getDriver() instanceof Sqlite) {
            $query->epilog('FOR UPDATE');
        }
        $query->firstOrFail();
    }

    /**
     * Reject stale forms rather than overwriting a changed procedure.
     *
     * @param array<string, mixed> $summary Fresh summary.
     * @return void
     */
    private function checkSnapshot(array $summary): void
    {
        if (!hash_equals($summary['snapshotHash'], (string)$this->getRequest()->getData('snapshot_hash'))) {
            throw new StaleSnapshotException(__d(
                'projects',
                'The procedure has changed since the form was opened. Check the current data and save again.',
            ));
        }
    }

    /**
     * Fields that actually differ from the stored values, as [old, new].
     *
     * @param \Cake\Datasource\EntityInterface $entity Patched entity.
     * @return array<string, array{mixed, mixed}>
     */
    private function changes(EntityInterface $entity): array
    {
        $changes = [];
        foreach ($entity->getDirty() as $field) {
            $old = $entity->getOriginal($field);
            $new = $entity->get($field);
            if (json_encode($old) !== json_encode($new)) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }

    /**
     * Compact audit text: only changed fields.
     *
     * @param array<string, mixed> $changes Changed fields.
     * @return string
     */
    private function describe(array $changes): string
    {
        return json_encode($changes, JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @param string $projectId Project ID.
     * @param string $message Plain text audit message.
     * @param string|null $caseId Optional case link.
     * @return void
     */
    private function logProcedure(string $projectId, string $message, ?string $caseId = null): void
    {
        $table = TableRegistry::getTableLocator()->get('App.Logs');
        $log = $table->newEntity([
            'model' => 'Project',
            'foreign_id' => $projectId,
            'user_id' => $this->getCurrentUser()->id,
            'action' => 'Comment',
            'descript' => h($message),
        ]);
        if ($caseId) {
            $url = Router::url([
                'plugin' => 'Projects', 'controller' => 'ProjectsAuthorities', 'action' => 'view', $caseId,
            ]);
            $log->descript .= ' <a href="' . h($url) . '">' . h(__d('projects', 'Procedure history')) . '</a>';
        }
        $table->saveOrFail($log);
    }

    /**
     * @param string $id Project ID.
     * @return \Cake\Http\Response
     */
    private function back(string $id): ?Response
    {
        return $this->redirect(['controller' => 'Projects', 'action' => 'view', $id, '?' => ['tab' => 'authorities']]);
    }
}
