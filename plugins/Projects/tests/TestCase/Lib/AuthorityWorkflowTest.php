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
    private function approve(Entity $response): Entity
    {
        $response->event_documents = [new Entity([
            'id' => 'link', 'role' => 'primary',
            'document' => new Entity([
                'id' => 'document', 'project_id' => 'project', 'owner_id' => 'company',
                'title' => 'Opinion', 'modified' => '2026-10-13 10:00:00', 'attachments' => [],
            ]),
        ])];
        $approval = $this->event('approve', 'approve', $response->id);
        $approval->evidence_hash = $this->workflow->evidenceHash($response, 'project', 'company');

        return $approval;
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
        $state = $this->workflow->phase($this->case($events), 'DGD', 'company');
        $this->assertSame('supplement', $state['state']);
        $this->assertSame(['r2'], array_keys($state['requests']));
        $events[] = $this->event('s3', 'supplement', 'r2');
        $state = $this->workflow->phase($this->case($events), 'DGD', 'company');
        $this->assertSame('waiting', $state['state']);
        $this->assertSame(['s3'], array_keys($state['pending']));
        $events[] = $this->event('response', 'opinion', 's3');
        $state = $this->workflow->phase($this->case($events), 'DGD', 'company');
        $this->assertSame('received', $state['state']);
        $this->assertEmpty($state['requests']);
        $this->assertEmpty($state['pending']);
    }

    /**
     * @return void
     */
    public function testUnrelatedResponseDoesNotResolveAnOpenRequest(): void
    {
        $events = [$this->event('r1', 'request'), $this->event('response', 'opinion')];
        $approval = $this->approve($events[1]);
        $events[] = $approval;
        $this->assertSame('supplement', $this->workflow->phase($this->case($events), 'DGD', 'company')['state']);
    }

    /**
     * @return void
     */
    public function testEarlyOpinionNeedsExplicitApproval(): void
    {
        $response = $this->event('response', 'opinion');
        $case = $this->case([$response]);
        $this->assertSame('received', $this->workflow->phase($case, 'DGD', 'company')['state']);
        $case->events = [$response, $this->approve($response)];
        $this->assertSame('complete', $this->workflow->phase($case, 'DGD', 'company')['state']);
    }

    /**
     * @return void
     */
    public function testChangedOrMovedEvidenceInvalidatesApproval(): void
    {
        $response = $this->event('response', 'opinion');
        $case = $this->case([$response, $this->approve($response)]);
        $response->event_documents[0]->document->modified = '2026-10-14 10:00:00';
        $this->assertSame('review', $this->workflow->phase($case, 'DGD', 'company')['state']);
        $response->event_documents[0]->document->project_id = 'other-project';
        $this->assertSame('review', $this->workflow->phase($case, 'DGD', 'company')['state']);
    }

    /**
     * @return void
     */
    public function testInvalidatingResponseAlsoInvalidatesDependentApproval(): void
    {
        $response = $this->event('response', 'opinion');
        $events = [$response, $this->approve($response), $this->event('void', 'void', 'response')];
        $this->assertEmpty($this->workflow->effectiveEvents($events));
        $this->assertSame('unsubmitted', $this->workflow->phase($this->case($events), 'DGD', 'company')['state']);
    }

    /**
     * @return void
     */
    public function testReadinessRequiresOpinionsAndFreshSnapshot(): void
    {
        $response = $this->event('response', 'opinion');
        $case = $this->case([$response, $this->approve($response)]);
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
        $approval = $this->approve($response);
        $events = [$response, $approval, $this->event('void1', 'void', 'response')];
        $this->assertEmpty($this->workflow->effectiveEvents($events));
        $this->assertSame(['void1'], array_map(fn($e) => $e->id, $this->workflow->selectableEvents($events)));
        $events[] = $this->event('void2', 'void', 'void1');
        $this->assertSame(
            ['response', 'approve'],
            array_map(fn($e) => $e->id, $this->workflow->effectiveEvents($events)),
        );
        $this->assertSame(
            ['response', 'approve', 'void2'],
            array_map(fn($e) => $e->id, $this->workflow->selectableEvents($events)),
        );
        $this->assertSame('complete', $this->workflow->phase($this->case($events), 'DGD', 'company')['state']);
    }

    /**
     * @return void
     */
    public function testAnyAnswerEndsTheWaitButCompletesNothing(): void
    {
        $events = [$this->event('s1', 'submission'), $this->event('o1', 'other', 's1')];
        $state = $this->workflow->phase($this->case($events), 'DGD', 'company');
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
        $events = [$this->event('s1', 'submission'), $response, $this->approve($response)];
        $events[] = $this->event('o1', 'other', 's1');
        $state = $this->workflow->phase($this->case($events), 'DGD', 'company');
        $this->assertSame('response', $state['received']->id);
        $this->assertSame('o1', $state['last']->id);
    }
}
