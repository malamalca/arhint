<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib;

use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;
use Projects\Lib\AuthorityWorkflow;

class AuthorityWorkflowTest extends TestCase
{
    /**
     * @var \Projects\Lib\AuthorityWorkflow
     */
    private AuthorityWorkflow $workflow;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->workflow = new AuthorityWorkflow();
    }

    /**
     * @param string $id Event identifier.
     * @param string $kind Event kind.
     * @param string|null $parent Parent ID.
     * @return \Cake\ORM\Entity
     */
    private function event(string $id, string $kind, ?string $parent = null): Entity
    {
        return new Entity([
            'id' => $id, 'kind' => $kind, 'parent_id' => $parent,
            'event_date' => '2026-10-13', 'event_documents' => [],
        ]);
    }

    /**
     * @param array<\Cake\ORM\Entity> $events History.
     * @return \Cake\ORM\Entity
     */
    private function case(array $events): Entity
    {
        // Events are numbered in the order in which they were recorded.
        foreach (array_values($events) as $position => $event) {
            $event->sequence ??= $position + 1;
        }

        return new Entity([
            'id' => 'case', 'project_id' => 'project', 'title' => 'Elektro', 'revision' => 'r1',
            'exempt_reason' => null, 'events' => $events,
            'authority' => new Entity(['id' => 'contact', 'owner_id' => 'company', 'kind' => 'C']),
        ]);
    }

    /**
     * @return \Cake\ORM\Entity
     */
    private function project(): Entity
    {
        return new Entity(['id' => 'project', 'owner_id' => 'company']);
    }

    /**
     * @param \Cake\ORM\Entity $response Response.
     * @return \Cake\ORM\Entity
     */
    private function withDocument(Entity $response): Entity
    {
        $response->event_documents = [new Entity([
            'id' => 'link', 'role' => 'primary',
            'document' => new Entity([
                'id' => 'document', 'project_id' => 'project', 'owner_id' => 'company',
                'title' => 'Opinion', 'modified' => '2026-10-13 10:00:00', 'attachments' => [],
            ]),
        ])];

        return $response;
    }

    /**
     * @return void
     */
    public function testMultipleSupplementsRemainLinkedToTheirRequests(): void
    {
        $events = [
            $this->event('s1', 'submission'),
            $this->event('r1', 'request', 's1'),
            $this->event('s2', 'supplement', 'r1'),
            $this->event('r2', 'request', 's2'),
        ];
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertSame('supplement', $state['state']);
        $this->assertSame(['r2'], array_keys($state['requests']));
        $events[] = $this->event('s3', 'supplement', 'r2');
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertSame('waiting', $state['state']);
        $this->assertSame(['s3'], array_keys($state['pending']));
        $events[] = $this->event('response', 'opinion', 's3');
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertSame('complete', $state['state']);
        $this->assertEmpty($state['requests']);
        $this->assertEmpty($state['pending']);
    }

    /**
     * @return void
     */
    public function testUnrelatedResponseDoesNotResolveAnOpenRequest(): void
    {
        $events = [$this->event('r1', 'request'), $this->event('response', 'opinion')];
        $this->assertSame('supplement', $this->workflow->phase($this->case($events), 'DGD')['state']);
    }

    /**
     * @return void
     */
    public function testReceivedOpinionCompletesWithoutConfirmation(): void
    {
        $case = $this->case([$this->event('s1', 'submission')]);
        $this->assertSame('waiting', $this->workflow->phase($case, 'DGD')['state']);
        $case->events[] = $this->event('response', 'opinion', 's1');
        $this->assertSame('complete', $this->workflow->phase($case, 'DGD')['state']);
        // An early opinion without any recorded submission is complete as well.
        $this->assertSame('complete', $this->workflow->phase($this->case([$this->event('o', 'opinion')]), 'DGD')['state']);
    }

    /**
     * Conditions complete only their own phase.
     *
     * @return void
     */
    public function testConditionsDoNotCompleteTheOpinion(): void
    {
        $case = $this->case([$this->event('s1', 'submission'), $this->event('c1', 'conditions', 's1')]);
        $this->assertSame('complete', $this->workflow->phase($case, 'DPP')['state']);
        $this->assertSame('unsubmitted', $this->workflow->phase($case, 'DGD')['state']);
    }

    /**
     * An expired opinion is reopened by a new submission and completed again by a new opinion.
     *
     * @return void
     */
    public function testNewSubmissionReopensACompletedOpinion(): void
    {
        $events = [$this->event('s1', 'submission'), $this->event('o1', 'opinion', 's1')];
        $this->assertSame('complete', $this->workflow->phase($this->case($events), 'DGD')['state']);
        $events[] = $this->event('s2', 'submission');
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertSame('waiting', $state['state']);
        $this->assertSame('o1', $state['received']->id);
        $summary = $this->workflow->summary($this->project(), [$this->case($events)], []);
        $this->assertFalse($summary['collected']);
        $events[] = $this->event('o2', 'opinion', 's2');
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertSame('complete', $state['state']);
        $this->assertSame('o2', $state['received']->id);
    }

    /**
     * @return void
     */
    public function testInvalidatingResponseReopensTheCase(): void
    {
        $response = $this->event('response', 'opinion');
        $events = [$response, $this->event('void', 'void', 'response')];
        $this->assertEmpty($this->workflow->effectiveEvents($events));
        $this->assertSame('unsubmitted', $this->workflow->phase($this->case($events), 'DGD')['state']);
    }

    /**
     * @return void
     */
    public function testReadinessRequiresOpinionsAndFreshSnapshot(): void
    {
        $response = $this->event('response', 'opinion');
        $case = $this->case([$this->withDocument($response)]);
        $project = $this->project();
        $summary = $this->workflow->summary($project, [$case], []);
        $this->assertTrue($summary['collected']);
        $ready = new Entity(['kind' => 'ready', 'snapshot_hash' => $summary['snapshotHash']]);
        $summary = $this->workflow->summary($project, [$case], [$ready]);
        $this->assertTrue($summary['collected']);
        $this->assertTrue($summary['ready']);
        $case->revision = 'r2';
        $summary = $this->workflow->summary($project, [$case], [$ready]);
        $this->assertFalse($summary['ready']);
    }

    /**
     * @return void
     */
    public function testOpenRequestBlocksAndUnansweredCaseIsMissing(): void
    {
        $case = $this->case([$this->event('request', 'request')]);
        $summary = $this->workflow->summary($this->project(), [$case], []);
        $this->assertTrue($summary['blocked']);
        $this->assertSame('supplement', $summary['rows']['case']['DGD']['state']);
        $this->assertSame(['Elektro'], $summary['missing']);
        $this->assertFalse($summary['collected']);
    }

    /**
     * @return void
     */
    public function testExemptCaseIsExcludedButEmptyListIsNeverReady(): void
    {
        $project = $this->project();
        $this->assertFalse($this->workflow->summary($project, [], [])['collected']);
        $case = $this->case([$this->event('request', 'request')]);
        $case->exempt_reason = 'Not affected by the building';
        $summary = $this->workflow->summary($project, [$case], []);
        $this->assertTrue($summary['collected']);
        $this->assertSame(0, $summary['required']);
        $this->assertSame(1, $summary['exempt']);
        $this->assertSame('exempt', $summary['rows']['case']['status']);
        $this->assertFalse($summary['blocked']);
    }

    /**
     * @return void
     */
    public function testInvalidationCanBeInvalidatedToRestoreTheEvent(): void
    {
        $response = $this->event('response', 'opinion');
        $events = [$response, $this->event('void1', 'void', 'response')];
        $this->assertEmpty($this->workflow->effectiveEvents($events));
        $this->assertSame(['void1'], array_map(fn($e) => $e->id, $this->workflow->selectableEvents($events)));
        $events[] = $this->event('void2', 'void', 'void1');
        $this->assertSame(['response'], array_map(fn($e) => $e->id, $this->workflow->effectiveEvents($events)));
        $this->assertSame(
            ['response', 'void2'],
            array_map(fn($e) => $e->id, $this->workflow->selectableEvents($events)),
        );
        $this->assertSame('complete', $this->workflow->phase($this->case($events), 'DGD')['state']);
    }

    /**
     * @return void
     */
    public function testAnyAnswerEndsTheWaitButCompletesNothing(): void
    {
        $events = [$this->event('s1', 'submission'), $this->event('o1', 'other', 's1')];
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertEmpty($state['pending']);
        $this->assertNotSame('waiting', $state['state']);
        $summary = $this->workflow->summary($this->project(), [$this->case($events)], []);
        $this->assertFalse($summary['collected']);
        $this->assertSame(['Elektro'], $summary['missing']);
    }

    /**
     * @return void
     */
    public function testReceivedResponseSurvivesLaterEvents(): void
    {
        $response = $this->event('response', 'opinion', 's1');
        $events = [$this->event('s1', 'submission'), $response];
        $events[] = $this->event('o1', 'other', 's1');
        $state = $this->workflow->phase($this->case($events), 'DGD');
        $this->assertSame('response', $state['received']->id);
        $this->assertSame('o1', $state['last']->id);
    }

    /**
     * @return void
     */
    public function testStatusFollowsTheProcedure(): void
    {
        $project = $this->project();
        $status = fn(array $events): string => $this->workflow
            ->summary($project, [$this->case($events)], [])['rows']['case']['status'];
        $this->assertSame('unsubmitted', $status([]));
        $this->assertSame('followup', $status([$this->event('s1', 'submission'), $this->event('c1', 'conditions', 's1')]));
        $this->assertSame(
            'waiting',
            $status([$this->event('s1', 'submission'), $this->event('c1', 'conditions', 's1'), $this->event('s2', 'submission')]),
        );
        $this->assertSame(
            'complete',
            $status([$this->event('c1', 'conditions'), $this->event('o1', 'opinion')]),
        );
    }

    /**
     * @param string $id Event identifier.
     * @param string $kind Event kind.
     * @param string $date Event date.
     * @param string|null $parent Parent ID.
     * @return \Cake\ORM\Entity
     */
    private function dated(string $id, string $kind, string $date, ?string $parent = null): Entity
    {
        $event = $this->event($id, $kind, $parent);
        $event->event_date = $date;

        return $event;
    }

    /**
     * A response recorded without a related event answers the open application.
     *
     * @return void
     */
    public function testResponseWithoutRelatedEventAnswersTheOpenApplication(): void
    {
        $events = [$this->event('s1', 'submission'), $this->event('c1', 'conditions')];
        $state = $this->workflow->phase($this->case($events), 'DPP');
        $this->assertEmpty($state['pending']);
        $this->assertSame('complete', $state['state']);
        $summary = $this->workflow->summary($this->project(), [$this->case($events)], []);
        $this->assertSame('followup', $summary['rows']['case']['status']);
        // Conditions answer the oldest open application, an opinion the latest one.
        $open = [$this->event('s1', 'submission'), $this->event('s2', 'submission')];
        $this->assertSame('s1', $this->workflow->defaultParent($open, 'conditions'));
        $this->assertSame('s2', $this->workflow->defaultParent($open, 'opinion'));
        $this->assertNull($this->workflow->defaultParent([], 'opinion'));
        $events = array_merge($open, [$this->event('c1', 'conditions')]);
        $this->assertSame(['s2'], array_keys($this->workflow->phase($this->case($events), 'DGD')['pending']));
    }

    /**
     * The order of events follows their dates, not the order of data entry.
     *
     * @return void
     */
    public function testLateEntryOfAnEarlierApplicationDoesNotReopenTheCase(): void
    {
        $events = [
            $this->dated('c1', 'conditions', '2026-10-09'),
            $this->dated('s1', 'submission', '2026-10-01'),
        ];
        $state = $this->workflow->phase($this->case($events), 'DPP');
        $this->assertSame('complete', $state['state']);
        $this->assertEmpty($state['pending']);
        $summary = $this->workflow->summary($this->project(), [$this->case($events)], []);
        $this->assertSame('followup', $summary['rows']['case']['status']);
        // A later application does open the case again.
        $events[] = $this->dated('s2', 'submission', '2026-10-15');
        $this->assertSame('waiting', $this->workflow->phase($this->case($events), 'DGD')['state']);
    }
}
