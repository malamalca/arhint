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
            'approve' => __d('projects', 'Suitability confirmed'),
            'revoke' => __d('projects', 'Suitability revoked'),
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
            'received' => __d('projects', 'Received / under review'),
            'complete' => __d('projects', 'Completed'),
            'review' => __d('projects', 'Review required'),
            'exempt' => __d('projects', 'Not required'),
        ];
    }

    /**
     * Parent links must remain in one case; early DPP opinions can be approved for DGD.
     * An invalidation can itself be invalidated, which restores the original event.
     *
     * @param string $kind New kind.
     * @param string $parentKind Parent kind.
     * @return bool
     */
    public static function canFollow(string $kind, string $parentKind): bool
    {
        if ($kind === 'approve') {
            return in_array($parentKind, ['conditions', 'opinion'], true);
        }

        return match ($kind) {
            'supplement' => $parentKind === 'request',
            'revoke' => $parentKind === 'approve',
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
            ->orderBy(['ProjectsAuthorityCases.title' => 'ASC', 'ProjectsAuthorityCases.id' => 'ASC'])
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
     * Fingerprint document membership, metadata and attachments for an approval.
     *
     * @param \Cake\Datasource\EntityInterface $event Response event.
     * @param string $projectId Expected project.
     * @param string $ownerId Expected company.
     * @param bool $requirePrimary Require a primary response document.
     * @return string|null Null if primary evidence is missing or has moved.
     */
    public function evidenceHash(
        EntityInterface $event,
        string $projectId,
        string $ownerId,
        bool $requirePrimary = true,
    ): ?string {
        $documents = [];
        $primary = false;
        foreach ($event->event_documents ?? [] as $link) {
            $document = $link->document;
            if (!$document || $document->project_id !== $projectId || $document->owner_id !== $ownerId) {
                return null;
            }
            $primary = $primary || $link->role === 'primary';
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

        return $primary || !$requirePrimary ? $this->hash($documents) : null;
    }

    /**
     * @param \Cake\Datasource\EntityInterface $case Case with events.
     * @param string $phase DPP or DGD.
     * @param string $ownerId Project company.
     * @return array<string, mixed>
     */
    public function phase(
        EntityInterface $case,
        string $phase,
        string $ownerId,
    ): array {
        $events = $this->effectiveEvents($case->events ?? []);
        $byId = [];
        $pending = [];
        $requests = [];
        $approval = null;
        $received = null;
        $last = null;
        foreach ($events as $event) {
            $byId[$event->id] = $event;
            $last = $event;
            $expectedKind = $phase === 'DPP' ? 'conditions' : 'opinion';
            if (in_array($event->kind, ['conditions', 'opinion'], true)) {
                unset($pending[(string)$event->parent_id]);
            }
            if (in_array($event->kind, ['conditions', 'opinion'], true) && $event->kind !== $expectedKind) {
                continue;
            }
            if ($event->kind === 'approve' && ($byId[$event->parent_id]->kind ?? '') !== $expectedKind) {
                continue;
            }
            switch ($event->kind) {
                case 'submission':
                case 'supplement':
                    unset($requests[(string)$event->parent_id]);
                    $pending[$event->id] = $event;
                    $approval = null;
                    break;
                case 'request':
                    unset($pending[(string)$event->parent_id]);
                    $requests[$event->id] = $event;
                    $approval = null;
                    break;
                case 'conditions':
                case 'opinion':
                    unset($pending[(string)$event->parent_id]);
                    $received = $event;
                    $approval = null;
                    break;
                case 'other':
                    // Any answer ends the wait; it only completes nothing by itself.
                    unset($pending[(string)$event->parent_id]);
                    break;
                case 'approve':
                    $approval = $event;
                    $received = $byId[$event->parent_id] ?? $received;
                    break;
                case 'revoke':
                    if ($approval && $approval->id === $event->parent_id) {
                        $approval = null;
                    }
                    break;
            }
        }
        $state = 'unsubmitted';
        if ($received) {
            $state = 'received';
        }
        if ($approval) {
            $parent = $byId[$approval->parent_id] ?? null;
            $hash = $parent ? $this->evidenceHash($parent, $case->project_id, $ownerId) : null;
            $matches = $hash && $approval->evidence_hash === $hash;
            $state = $matches ? 'complete' : 'review';
        }
        if ($pending) {
            $state = 'waiting';
        }
        if ($requests) {
            $state = 'supplement';
        }

        return compact('state', 'last', 'pending', 'requests', 'approval', 'received');
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
            unset($data['events'], $data['authority'], $data['user'], $data['created'], $data['modified']);
            $data['contact_available'] = $case->authority !== null;
            $list[$case->id] = $data;
            foreach ($case->events ?? [] as $event) {
                $eventData = $event->toArray();
                unset($eventData['user'], $eventData['event_documents']);
                $history[$event->id] = [
                    $eventData, $this->evidenceHash($event, $project->id, $project->owner_id, false),
                ];
            }
            $dpp = $this->phase($case, 'DPP', $project->owner_id);
            $dgd = $this->phase($case, 'DGD', $project->owner_id);
            $isExempt = self::isExempt($case);
            $status = $isExempt ? 'exempt' : $dgd['state'];
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
