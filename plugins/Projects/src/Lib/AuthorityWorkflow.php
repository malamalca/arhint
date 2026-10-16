<?php
declare(strict_types=1);

namespace Projects\Lib;

use Cake\Datasource\EntityInterface;
use Cake\I18n\Date;
use Cake\ORM\Entity;
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
            'complete' => __d('projects', 'Opinion received'),
            'followup' => __d('projects', 'Conditions received'),
            'expired' => __d('projects', 'Validity expired'),
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
     * Received conditions or an opinion whose last day of validity has passed.
     *
     * @param \Cake\Datasource\EntityInterface|null $response Received conditions or opinion.
     * @return bool
     */
    public static function isExpired(?EntityInterface $response): bool
    {
        $valid = $response?->date_valid;
        if (!$valid) {
            return false;
        }

        return self::day($valid) < Date::today()->format('Y-m-d');
    }

    /**
     * A received opinion completes the procedure; a new submission or request reopens it
     * until it is answered again.
     *
     * Events are read in the order in which things happened (their date), then in the order
     * in which they were recorded, so a late entry of an earlier application cannot reopen the case.
     *
     * @param \Cake\Datasource\EntityInterface $case Case with events.
     * @param string $phase DPP (conditions) or DGD (opinion).
     * @return array<string, mixed>
     */
    public function phase(EntityInterface $case, string $phase): array
    {
        $effective = $this->effectiveEvents($case->events ?? []);
        $last = $effective ? $effective[array_key_last($effective)] : null;
        $expectedKind = $phase === 'DPP' ? 'conditions' : 'opinion';
        $pending = [];
        $requests = [];
        $received = null;
        foreach ($this->chronological($effective) as $event) {
            if (in_array($event->kind, ['conditions', 'opinion'], true)) {
                $this->answer($pending, $event);
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
                    $this->answer($pending, $event);
                    $requests[$event->id] = $event;
                    break;
                case 'other':
                    // Any answer ends the wait; it only completes nothing by itself.
                    $this->answer($pending, $event);
                    break;
            }
        }
        // An opinion answers every application recorded before it, even one dated later than the
        // opinion itself (a response is often entered after the application, with the document's older date).
        if ($received && $expectedKind === 'opinion') {
            $pending = array_filter(
                $pending,
                fn($event) => (int)$event->sequence > (int)$received->sequence,
            );
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
     * The open application a new response without an explicit related event refers to.
     *
     * Conditions arrive before opinions, so they answer the oldest open application and
     * every other response answers the latest one.
     *
     * @param array<\Cake\Datasource\EntityInterface> $events Events of the case in sequence order.
     * @param string $kind Kind of the new response.
     * @return string|null Event ID, or null if nothing is open.
     */
    public function defaultParent(array $events, string $kind): ?string
    {
        $pending = $this->phase(new Entity(['events' => $events]), 'DGD')['pending'];

        return $this->pendingKey($pending, $kind);
    }

    /**
     * Close the application a response answers: the related one, or else the one it most likely refers to.
     *
     * @param array<string, \Cake\Datasource\EntityInterface> $pending Open applications.
     * @param \Cake\Datasource\EntityInterface $response Response event.
     * @return void
     */
    private function answer(array &$pending, EntityInterface $response): void
    {
        $key = $response->parent_id ? (string)$response->parent_id : $this->pendingKey($pending, $response->kind);
        if ($key !== null) {
            unset($pending[$key]);
        }
    }

    /**
     * @param array<string, \Cake\Datasource\EntityInterface> $pending Open applications in order.
     * @param string $kind Kind of the response.
     * @return string|null
     */
    private function pendingKey(array $pending, string $kind): ?string
    {
        if (!$pending) {
            return null;
        }

        return (string)($kind === 'conditions' ? array_key_first($pending) : array_key_last($pending));
    }

    /**
     * @param mixed $date Date object or ISO date string.
     * @return string Y-m-d
     */
    private static function day(mixed $date): string
    {
        // Cake's Date is not a DateTimeInterface and its string form is localized.
        return is_object($date) && method_exists($date, 'format') ?
            $date->format('Y-m-d') : substr((string)$date, 0, 10);
    }

    /**
     * Sort by event date, keeping the recorded order of events on the same day.
     *
     * @param array<\Cake\Datasource\EntityInterface> $events Events in recorded order.
     * @return array<\Cake\Datasource\EntityInterface>
     */
    private function chronological(array $events): array
    {
        $day = fn(EntityInterface $event): string => self::day($event->event_date);
        $indexed = [];
        foreach (array_values($events) as $position => $event) {
            $indexed[] = [$day($event), $position, $event];
        }
        usort($indexed, fn(array $a, array $b): int => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        return array_column($indexed, 2);
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
            // Conditions answer the applications recorded before them; if nothing newer is open, they are in.
            if (
                !$isExempt && !$dgd['received'] && $dpp['received'] && $status !== 'supplement' && !array_filter(
                    $dgd['pending'],
                    fn($event) => (int)$event->sequence > (int)$dpp['received']->sequence,
                )
            ) {
                $status = 'followup';
            }
            if ($status === 'complete' && self::isExpired($dgd['received'])) {
                $status = 'expired';
            } elseif ($status === 'followup' && self::isExpired($dpp['received'])) {
                $status = 'expired';
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
            if ($dgd['state'] === 'complete' && !self::isExpired($dgd['received'])) {
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
