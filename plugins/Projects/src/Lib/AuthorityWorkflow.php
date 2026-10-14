<?php
declare(strict_types=1);

namespace Projects\Lib;

use Cake\Datasource\EntityInterface;
use Cake\ORM\TableRegistry;

/**
 * Derive procedure state from immutable events, not editable status fields.
 */
class AuthorityWorkflow
{
    /**
     * @return array<string, string>
     */
    public static function kinds(): array
    {
        return [
            'submission' => __d('projects', 'Application submitted'),
            'conditions' => __d('projects', 'Conditions received'),
            'opinion' => __d('projects', 'Opinion received'),
            'request' => __d('projects', 'Supplement requested'),
            'supplement' => __d('projects', 'Supplement submitted'),
            'other' => __d('projects', 'Other response'),
            'void' => __d('projects', 'Event invalidated'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function states(): array
    {
        return [
            'unsubmitted' => __d('projects', 'Not submitted'),
            'waiting' => __d('projects', 'Waiting for response'),
            'supplement' => __d('projects', 'Supplement required'),
            'complete' => __d('projects', 'Completed'),
            'followup' => __d('projects', 'Application for opinion follows'),
            'exempt' => __d('projects', 'Not required'),
        ];
    }

    /**
     * Parent links must remain in one case.
     * An invalidation can itself be invalidated, which restores the original event.
     *
     * @param string $kind New kind.
     * @param string $parentKind Parent kind.
     * @return bool
     */
    public static function canFollow(string $kind, string $parentKind): bool
    {
        return match ($kind) {
            'supplement' => $parentKind === 'request',
            'void' => true,
            'conditions', 'opinion', 'request', 'other' => in_array($parentKind, ['submission', 'supplement'], true),
            default => false,
        };
    }

    /**
     * Load a complete history after project authorization.
     *
     * @param string $projectId Project ID.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    public function cases(string $projectId): array
    {
        $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectId);

        return TableRegistry::getTableLocator()->get('Projects.ProjectsAuthorityCases')->find()
            ->where(['ProjectsAuthorityCases.project_id' => $projectId])
            ->contain(['Authorities' => [
                'queryBuilder' => fn($query) => $query->where([
                    'Authorities.owner_id' => $project->owner_id, 'Authorities.kind' => 'C',
                ]),
            ], 'Users', 'Events' => ['Users', 'EventDocuments' => ['Documents' => [
                'queryBuilder' => fn($query) => $query->where([
                    'Documents.project_id' => $projectId, 'Documents.owner_id' => $project->owner_id,
                ]),
                'Attachments',
            ]]]])
            ->orderBy([
                'ProjectsAuthorityCases.sort_order' => 'ASC',
                'ProjectsAuthorityCases.title' => 'ASC',
                'ProjectsAuthorityCases.id' => 'ASC',
            ])
            ->all()->toList();
    }

    /**
     * A case excluded from the review by an explicit reason.
     *
     * @param \Cake\Datasource\EntityInterface $case Case.
     * @return bool
     */
    public static function isExempt(EntityInterface $case): bool
    {
        return trim((string)$case->exempt_reason) !== '';
    }

    /**
     * Invalidation also invalidates dependent responses and approvals; invalidating the
     * invalidation restores them.
     *
     * @param array<\Cake\Datasource\EntityInterface> $events Events in sequence order.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    public function effectiveEvents(array $events): array
    {
        $state = $this->resolve($events);

        return array_values(array_filter(
            $events,
            fn($event) => $event->kind !== 'void' && $state['valid']($event),
        ));
    }

    /**
     * Events that can be chosen as a parent: effective events and active invalidations.
     *
     * @param array<\Cake\Datasource\EntityInterface> $events Events in sequence order.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    public function selectableEvents(array $events): array
    {
        $state = $this->resolve($events);

        return array_values(array_filter(
            $events,
            fn($event) => $event->kind === 'void' ? $state['active']($event) : $state['valid']($event),
        ));
    }

    /**
     * @param array<\Cake\Datasource\EntityInterface> $events Events in sequence order.
     * @return array{valid: \Closure, active: \Closure}
     */
    private function resolve(array $events): array
    {
        $byId = [];
        $voidsOf = [];
        foreach ($events as $event) {
            $byId[$event->id] = $event;
            if ($event->kind === 'void' && $event->parent_id) {
                $voidsOf[$event->parent_id][] = $event;
            }
        }
        $memo = [];
        $active = null;
        $valid = null;
        // Parents always precede their children, so the recursion terminates; the memo guards bad data.
        $active = function (EntityInterface $void) use (&$active, $voidsOf, &$memo): bool {
            $key = 'a' . $void->id;
            if (isset($memo[$key])) {
                return $memo[$key];
            }
            $memo[$key] = true;
            foreach ($voidsOf[$void->id] ?? [] as $cancel) {
                if ($active($cancel)) {
                    return $memo[$key] = false;
                }
            }

            return true;
        };
        $valid = function (EntityInterface $event) use (&$valid, $active, $voidsOf, $byId, &$memo): bool {
            $key = 'v' . $event->id;
            if (isset($memo[$key])) {
                return $memo[$key];
            }
            $memo[$key] = true;
            foreach ($voidsOf[$event->id] ?? [] as $void) {
                if ($active($void)) {
                    return $memo[$key] = false;
                }
            }
            $parent = $event->parent_id ? ($byId[$event->parent_id] ?? null) : null;
            if ($parent && $parent->kind !== 'void' && !$valid($parent)) {
                return $memo[$key] = false;
            }

            return true;
        };

        return ['valid' => $valid, 'active' => $active];
    }

    /**
     * Fingerprint document membership, metadata and attachments of an event.
     *
     * @param \Cake\Datasource\EntityInterface $event Event with its document links.
     * @param string $projectId Expected project.
     * @param string $ownerId Expected company.
     * @return string|null Null if a linked document is missing or has moved to another project.
     */
    public function documentsHash(EntityInterface $event, string $projectId, string $ownerId): ?string
    {
        $documents = [];
        foreach ($event->event_documents ?? [] as $link) {
            $document = $link->document;
            if (!$document || $document->project_id !== $projectId || $document->owner_id !== $ownerId) {
                return null;
            }
            $attachments = [];
            foreach ($document->attachments ?? [] as $attachment) {
                $attachments[] = $attachment->toArray();
            }
            usort($attachments, fn($a, $b) => strcmp($a['id'], $b['id']));
            $data = $document->toArray();
            unset($data['attachments']);
            $documents[$link->id] = [$link->role, $data, $attachments];
        }
        ksort($documents);

        return $this->hash($documents);
    }

    /**
     * A received opinion completes the procedure; a new submission or request reopens it
     * until it is answered again.
     *
     * @param \Cake\Datasource\EntityInterface $case Case with events.
     * @param string $phase DPP (conditions) or DGD (opinion).
     * @return array<string, mixed>
     */
    public function phase(EntityInterface $case, string $phase): array
    {
        $pending = [];
        $requests = [];
        $received = null;
        $last = null;
        $expectedKind = $phase === 'DPP' ? 'conditions' : 'opinion';
        foreach ($this->effectiveEvents($case->events ?? []) as $event) {
            $last = $event;
            if (in_array($event->kind, ['conditions', 'opinion'], true)) {
                unset($pending[(string)$event->parent_id]);
                if ($event->kind === $expectedKind) {
                    $received = $event;
                }
                continue;
            }
            switch ($event->kind) {
                case 'submission':
                case 'supplement':
                    unset($requests[(string)$event->parent_id]);
                    $pending[$event->id] = $event;
                    break;
                case 'request':
                    unset($pending[(string)$event->parent_id]);
                    $requests[$event->id] = $event;
                    break;
                case 'other':
                    // Any answer ends the wait; it only completes nothing by itself.
                    unset($pending[(string)$event->parent_id]);
                    break;
            }
        }
        $state = $received ? 'complete' : 'unsubmitted';
        if ($pending) {
            $state = 'waiting';
        }
        if ($requests) {
            $state = 'supplement';
        }

        return compact('state', 'last', 'pending', 'requests', 'received');
    }

    /**
     * @param \Cake\Datasource\EntityInterface $project Project.
     * @param array<\Cake\Datasource\EntityInterface> $cases Cases.
     * @param array<\Cake\Datasource\EntityInterface> $reviews Reviews ordered by creation.
     * @return array<string, mixed>
     */
    public function summary(EntityInterface $project, array $cases, array $reviews): array
    {
        $list = [];
        $history = [];
        $rows = [];
        $missing = [];
        $required = 0;
        $complete = 0;
        $determined = true;
        $blocked = false;
        $exempt = 0;
        foreach ($cases as $case) {
            $data = $case->toArray();
            // The display order is not part of the procedure: reordering must not withdraw a confirmation.
            unset($data['events'], $data['authority'], $data['user'], $data['created'], $data['modified']);
            unset($data['sort_order']);
            $data['contact_available'] = $case->authority !== null;
            $list[$case->id] = $data;
            foreach ($case->events ?? [] as $event) {
                $eventData = $event->toArray();
                unset($eventData['user'], $eventData['event_documents']);
                $history[$event->id] = [
                    $eventData, $this->documentsHash($event, $project->id, $project->owner_id),
                ];
            }
            $dpp = $this->phase($case, 'DPP');
            $dgd = $this->phase($case, 'DGD');
            $isExempt = self::isExempt($case);
            $status = $isExempt ? 'exempt' : $dgd['state'];
            // Conditions are in, nothing has been submitted for the opinion yet.
            if ($status === 'unsubmitted' && $dpp['received']) {
                $status = 'followup';
            }
            $rows[$case->id] = ['DPP' => $dpp, 'DGD' => $dgd, 'status' => $status];
            if ($isExempt) {
                $exempt++;
                continue;
            }
            if (!$case->authority) {
                $determined = false;
            }
            $required++;
            if ($dgd['state'] === 'complete') {
                $complete++;
            } else {
                $missing[] = $case->title;
            }
            foreach ([$dpp, $dgd] as $phaseState) {
                $blocked = $blocked || (bool)$phaseState['pending'] || (bool)$phaseState['requests'];
            }
        }
        ksort($list);
        ksort($history);
        $listHash = $this->hash($list);
        $snapshotHash = $this->hash([
            $listHash, $history,
        ]);
        $readyReview = null;
        foreach ($reviews as $review) {
            if ($review->kind === 'ready' || $review->kind === 'revoke') {
                $readyReview = $review;
            }
        }
        $collected = $determined && $cases !== [] && !$blocked && $required === $complete;
        $ready = $collected && $readyReview && $readyReview->kind === 'ready' &&
            $readyReview->snapshot_hash === $snapshotHash;

        return compact(
            'rows',
            'missing',
            'required',
            'complete',
            'exempt',
            'determined',
            'blocked',
            'listHash',
            'snapshotHash',
            'collected',
            'ready',
            'readyReview',
        );
    }

    /**
     * @param mixed $value Snapshot value.
     * @return string
     */
    private function hash(mixed $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }
}
