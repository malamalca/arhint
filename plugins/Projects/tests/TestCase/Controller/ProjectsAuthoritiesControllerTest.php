<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Controller;

use Cake\I18n\Date;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Text;
use DOMDocument;
use DOMXPath;
use Projects\Lib\AuthorityCalendar;
use Projects\Lib\AuthorityWorkflow;

class ProjectsAuthoritiesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * @var array<string>
     */
    public array $fixtures = [
        'app.Users', 'app.Logs', 'app.Attachments',
        'plugin.Projects.Projects', 'plugin.Projects.ProjectsUsers',
        'plugin.Crm.Contacts', 'plugin.Projects.ProjectsAuthorityCases',
        'plugin.Projects.ProjectsAuthorityEvents', 'plugin.Projects.ProjectsAuthorityEventDocuments',
        'plugin.Projects.ProjectsAuthorityReviews',
        'plugin.Projects.ProjectsAuthorityCalendarLinks', 'plugin.Calendar.Events',
        'plugin.Documents.Documents', 'plugin.Documents.DocumentsCounters',
    ];

    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';
    private const DOCUMENT = 'd0d59a31-6de7-4eb4-8230-ca09113a7fe6';

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(USER_ADMIN)]);
        $this->enableCsrfToken();
        $this->enableSecurityToken();
    }

    /**
     * @return string
     */
    private function createCase(): string
    {
        $cases = $this->getTableLocator()->get('Projects.ProjectsAuthorityCases');
        $case = $cases->newEmptyEntity();
        $case->project_id = self::PROJECT;
        $case->contact_id = COMPANY_FIRST;
        $case->title = 'Elektro';
        $case->revision = Text::uuid();
        $cases->saveOrFail($case);

        return $case->id;
    }

    /**
     * Personal opt-in stays unique, follows the effective deadline, and protects its source.
     *
     * @return void
     */
    public function testCalendarDeadlineLifecycle(): void
    {
        $id = $this->createCase();
        $this->post('/projects/projects-authorities/event/' . $id, [
            'kind' => 'submission', 'event_date' => '2026-10-13', 'date_due' => '2026-10-20',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $source = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents')->find()->where(['case_id' => $id])->firstOrFail();
        $links = $this->getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        $events = $this->getTableLocator()->get('Calendar.Events');
        $this->assertSame(0, $links->find()->count());
        foreach ([1, 2] as $attempt) {
            $this->post('/projects/projects-authorities/calendar/' . $id, ['calendar_id' => USER_COMMON]);
            $this->assertResponseCode(302);
            $this->assertSame(1, $links->find()->count());
        }
        $link = $links->find()->firstOrFail();
        $entry = $events->get($link->event_id);
        $this->assertSame(USER_ADMIN, $entry->calendar_id);
        $this->assertTrue($entry->all_day);
        $this->assertSame('2026-10-20', $entry->dat_start->format('Y-m-d'));
        $this->assertStringContainsString($id, $entry->body);
        $entry->title = 'Independent edit';
        $this->assertFalse($events->save($entry));
        $this->assertFalse($events->delete($entry));
        $this->get('/calendar/events/edit/' . $entry->id);
        $this->assertRedirect('/projects/projects-authorities/view/' . $id);
        $this->post('/projects/projects-authorities/edit-event/' . $source->id, [
            'kind' => 'submission', 'event_date' => '2026-10-13', 'date_due' => '2026-10-22',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $this->assertSame('2026-10-22', $events->get($entry->id)->dat_start->format('Y-m-d'));
        $this->post('/projects/projects-authorities/event/' . $id, [
            'kind' => 'submission', 'event_date' => '2026-10-14',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $this->assertFalse($events->exists(['id' => $entry->id]));
        $this->assertNull($links->get($link->id)->event_id);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'kind' => 'submission', 'event_date' => '2026-10-15', 'date_due' => '2026-10-25',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $renewed = $links->get($link->id);
        $this->assertSame('2026-10-25', $events->get($renewed->event_id)->dat_start->format('Y-m-d'));
        $this->post('/projects/projects-authorities/event/' . $id, [
            'kind' => 'void', 'parent_id' => $renewed->source_event_id, 'descript' => 'Wrong event',
            'event_date' => '2026-10-16', 'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $this->assertFalse($events->exists(['id' => $renewed->event_id]));
        $this->assertNull($links->get($link->id)->event_id);
        $this->post('/projects/projects-authorities/calendar/' . $id, ['remove' => '1']);
        $this->assertResponseCode(302);
        $this->assertSame(0, $links->find()->count());
    }

    /**
     * @return void
     */
    public function testCalendarSubscriptionRequiresProjectAccessAndPost(): void
    {
        $id = $this->createCase();
        $this->get('/projects/projects-authorities/calendar/' . $id);
        $this->assertResponseCode(405);
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(USER_COMMON)]);
        $this->post('/projects/projects-authorities/calendar/' . $id);
        $this->assertResponseCode(403);
        $this->assertSame(0, $this->getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks')->find()->count());
    }

    /**
     * Losing project membership removes the subscriber's copied project information.
     *
     * @return void
     */
    public function testCalendarLinkIsRemovedWithProjectAccess(): void
    {
        $id = $this->createCase();
        $this->post('/projects/projects-authorities/event/' . $id, [
            'kind' => 'submission', 'event_date' => '2026-10-13', 'date_due' => '2026-10-20',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $members = $this->getTableLocator()->get('Projects.ProjectsUsers');
        $member = $members->newEntity(['project_id' => self::PROJECT, 'user_id' => USER_COMMON]);
        $members->saveOrFail($member);
        (new AuthorityCalendar())->subscribe($id, $this->getTableLocator()->get('Users')->get(USER_COMMON));
        $links = $this->getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        $link = $links->find()->where(['case_id' => $id, 'calendar_id' => USER_COMMON])->firstOrFail();
        $this->assertNotNull($link->event_id);
        $members->deleteOrFail($member);
        $this->assertFalse($links->exists(['id' => $link->id]));
        $this->assertFalse($this->getTableLocator()->get('Calendar.Events')->exists(['id' => $link->event_id]));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(): array
    {
        $workflow = new AuthorityWorkflow();
        $project = $this->getTableLocator()->get('Projects.Projects')->get(self::PROJECT);
        $reviews = $this->getTableLocator()->get('Projects.ProjectsAuthorityReviews')->find()
            ->where(['project_id' => self::PROJECT])->orderBy(['sequence'])->all()->toList();

        return $workflow->summary($project, $workflow->cases(self::PROJECT), $reviews);
    }

    /**
     * @param string $kind Review kind.
     * @param array<string, mixed> $data Extra form data.
     * @return void
     */
    private function review(string $kind, array $data = []): void
    {
        $this->post('/projects/projects-authorities/review/' . self::PROJECT, $data + [
            'kind' => $kind, 'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
    }

    /**
     * @return void
     */
    public function testScreensUseExistingProjectAndFormSystems(): void
    {
        $id = $this->createCase();
        foreach (
            [
            '/projects/projects/view/' . self::PROJECT . '?tab=authorities',
            '/projects/projects-authorities/index?project=' . self::PROJECT,
            '/projects/projects-authorities/edit-case?project=' . self::PROJECT,
            '/projects/projects-authorities/edit-case/' . $id,
            '/projects/projects-authorities/view/' . $id,
            '/projects/projects-authorities/event/' . $id,
            ] as $url
        ) {
            $this->get($url);
            $this->assertResponseOk($url . ' ' . ($this->_exception?->getMessage() ?? ''));
        }
    }

    /**
     * Autocomplete changes a hidden ID after rendering; only that ID is unlocked.
     *
     * @return void
     */
    public function testNewCaseAutocompleteAndContactValidation(): void
    {
        $url = '/projects/projects-authorities/edit-case?project=' . self::PROJECT;
        $this->get($url);
        $this->assertResponseOk();
        $document = new DOMDocument();
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML((string)$this->_response->getBody());
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new DOMXPath($document);
        $unlocked = $xpath->query('//input[@name="_Token[unlocked]"]')->item(0);
        $this->assertNotNull($unlocked);
        $this->assertStringContainsString('contact_id', $unlocked->getAttribute('value'));
        $this->assertStringNotContainsString('snapshot_hash', $unlocked->getAttribute('value'));
        $data = [
            'contact_id' => COMPANY_FIRST, 'contact_title' => 'Arhim d.o.o.', 'title' => 'Elektro',
            'user_id' => USER_ADMIN,
            'snapshot_hash' => $this->summary()['snapshotHash'],
            // A forged project ID must not move the case away from the authorized URL.
            'project_id' => '4dd53305-9715-4be4-b169-20defe113d2b',
        ];
        $this->post($url, $data);
        $this->assertResponseCode(302);
        $cases = $this->getTableLocator()->get('Projects.ProjectsAuthorityCases');
        $case = $cases->find()->firstOrFail();
        $this->assertSame(COMPANY_FIRST, $case->contact_id);
        $this->assertSame(self::PROJECT, $case->project_id);
        $data['contact_id'] = '49a90cfe-fda4-49ca-b7ec-ca50783b5a43';
        $data['snapshot_hash'] = $this->summary()['snapshotHash'];
        $this->post($url, $data);
        $this->assertResponseOk();
        $this->assertSame(1, $cases->find()->count());
        // A person from the same company is also not an authority company.
        $data['contact_id'] = '49a90cfe-fda4-49ca-b7ec-ca50783b5a42';
        $this->post($url, $data);
        $this->assertResponseOk();
        $this->assertSame(1, $cases->find()->count());
    }

    /**
     * @return void
     */
    public function testNoRequirementFieldsAndResponsiblePersonRequiresMembership(): void
    {
        $data = [
            'contact_id' => COMPANY_FIRST, 'title' => 'Elektro',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ];
        $url = '/projects/projects-authorities/edit-case?project=' . self::PROJECT;
        $this->post($url, $data);
        $this->assertResponseCode(302);
        $cases = $this->getTableLocator()->get('Projects.ProjectsAuthorityCases');
        $this->assertSame(1, $cases->find()->count());
        $data['user_id'] = USER_COMMON;
        $data['snapshot_hash'] = $this->summary()['snapshotHash'];
        $this->post($url, $data);
        $this->assertResponseOk();
        $this->assertSame(1, $cases->find()->count());
    }

    /**
     * Browser multiple-select controls post an empty hidden value even without a selection.
     *
     * @return void
     */
    public function testSubmissionAcceptsEmptyDocumentSelectFallbacks(): void
    {
        $id = $this->createCase();
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'submission',
            'event_date' => '2026-10-13',
            'document_ids' => [''],
        ]);
        $this->assertResponseCode(302);
        $this->assertSame(1, $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents')->find()->count());
        $this->assertSame(0, $this->getTableLocator()->get('Projects.ProjectsAuthorityEventDocuments')->find()->count());
    }

    /**
     * The latest receipt is linked to its first document and shows the document issue date.
     *
     * @return void
     */
    public function testReceiptDateLinksDocumentAndHasGreenHighlight(): void
    {
        $id = $this->createCase();
        $this->getTableLocator()->get('Documents.Documents')->updateAll([
            'project_id' => self::PROJECT, 'dat_issue' => '2026-09-08',
        ], ['id' => self::DOCUMENT]);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'opinion',
            'event_date' => '2026-10-13', 'document_ids' => [self::DOCUMENT],
        ]);
        $this->assertResponseCode(302);
        $this->get('/projects/projects/view/' . self::PROJECT . '?tab=authorities');
        $this->assertResponseOk();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML((string)$this->_response->getBody());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $headers = $xpath->query('//table[@id="projects-authorities"]/thead/tr/th');
        $this->assertSame(5, $headers->length);
        $cell = $xpath->query('//table[@id="projects-authorities"]/tbody/tr/td[4]')->item(0);
        $this->assertNotNull($cell);
        $link = $xpath->query('.//a', $cell)->item(0);
        $this->assertNotNull($link);
        $this->assertStringContainsString(self::DOCUMENT, $link->getAttribute('href'));
        $this->assertSame('08.09.2026', $link->textContent);
        $this->assertStringNotContainsString('check', $cell->textContent);
        $this->assertSame('authority-receipt', $xpath->query('.//div', $cell)->item(0)->getAttribute('class'));
    }

    /**
     * Editing keeps event order and author, updates the deadline, and records an audit log.
     *
     * @return void
     */
    public function testEditEventUpdatesDeadlineAndDocumentLinks(): void
    {
        $id = $this->createCase();
        $documents = $this->getTableLocator()->get('Documents.Documents');
        $documents->updateAll(['project_id' => self::PROJECT], ['id' => self::DOCUMENT]);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'submission',
            'event_date' => '2026-10-13', 'date_due' => '2026-10-18',
        ]);
        $this->assertResponseCode(302);
        $events = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $event = $events->find()->where(['case_id' => $id])->firstOrFail();
        $url = '/projects/projects-authorities/edit-event/' . $event->id;
        $this->get($url);
        $this->assertResponseOk();
        $this->assertResponseContains('Povezani dogodek');
        $this->post($url, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'submission',
            'event_date' => '2026-10-13', 'date_due' => '2026-10-20', 'descript' => 'Updated',
            'document_ids' => [self::DOCUMENT],
        ]);
        $this->assertResponseCode(302);
        $updated = $events->get($event->id, contain: ['EventDocuments']);
        $this->assertSame('2026-10-20', $updated->date_due->format('Y-m-d'));
        $this->assertSame($event->sequence, $updated->sequence);
        $this->assertSame($event->user_id, $updated->user_id);
        $this->assertCount(1, $updated->event_documents);
        $this->assertSame(self::DOCUMENT, $updated->event_documents[0]->document_id);
        $this->assertSame(1, $events->find()->where(['case_id' => $id])->count());
        $this->assertGreaterThanOrEqual(2, $this->getTableLocator()->get('Logs')->find()
            ->where(['model' => 'Project', 'foreign_id' => self::PROJECT])->count());
        $this->get('/projects/projects/view/' . self::PROJECT . '?tab=authorities');
        $this->assertResponseOk();
        $this->assertResponseContains('authority-deadline');
        $this->assertResponseContains('20. 10. 2026');
        $this->post($url, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'submission',
            'event_date' => '2026-10-13', 'date_due' => '', 'document_ids' => [''],
        ]);
        $this->assertResponseCode(302);
        $updated = $events->get($event->id, contain: ['EventDocuments']);
        $this->assertNull($updated->date_due);
        $this->assertEmpty($updated->event_documents);
    }

    /**
     * The latest supplement request has its own column with a linked caption and deadline.
     *
     * @return void
     */
    public function testSupplementRequestColumnShowsLinkedCaptionAndDeadline(): void
    {
        $id = $this->createCase();
        $this->getTableLocator()->get('Documents.Documents')->updateAll(['project_id' => self::PROJECT], ['id' => self::DOCUMENT]);
        $due = Date::today()->subDays(1);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'request',
            'event_date' => $due->subDays(5)->format('Y-m-d'), 'date_due' => $due->format('Y-m-d'), 'document_ids' => [self::DOCUMENT],
        ]);
        $this->assertResponseCode(302);
        $this->get('/projects/projects/view/' . self::PROJECT . '?tab=authorities');
        $this->assertResponseOk();
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML((string)$this->_response->getBody());
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new DOMXPath($document);
        $cell = $xpath->query('//table[@id="projects-authorities"]/tbody/tr/td[5]')->item(0);
        $this->assertNotNull($cell);
        $caption = $xpath->query('.//strong/a', $cell)->item(0);
        $this->assertNotNull($caption);
        $this->assertStringContainsString(self::DOCUMENT, $caption->getAttribute('href'));
        $this->assertSame('Rok: ' . $due->format('d.m.Y'), $caption->textContent);
        $this->assertStringNotContainsString('Zahteva za dopolnitev', $cell->textContent);
        $this->assertSame('Rok je potekel', $xpath->query('.//div', $cell)->item(0)->getAttribute('title'));
        $this->assertSame(1, $xpath->query('.//a', $cell)->length);
        $this->assertStringContainsString('overdue', $xpath->query('.//div', $cell)->item(0)->getAttribute('class'));
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'other', 'event_date' => '2026-10-13',
        ]);
        $this->assertResponseCode(302);
        $this->get('/projects/projects/view/' . self::PROJECT . '?tab=authorities');
        $this->assertResponseOk();
        // An unrelated later event does not answer the request, so its deadline stays visible.
        $this->assertResponseContains('class="authority-deadline overdue"');
    }

    /**
     * @return void
     */
    public function testOpinionApprovalAndReadinessLifecycle(): void
    {
        $id = $this->createCase();
        $this->getTableLocator()->get('Documents.Documents')->updateAll(['project_id' => self::PROJECT], ['id' => self::DOCUMENT]);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'opinion',
            'event_date' => '2026-10-13',
            'document_ids' => [self::DOCUMENT],
        ]);
        $this->assertResponseCode(302);
        $events = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $response = $events->find()->where(['case_id' => $id])->firstOrFail();
        $this->assertFalse($this->summary()['collected']);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'approve',
            'parent_id' => $response->id, 'event_date' => '2026-10-13',
        ]);
        $this->assertResponseCode(302);
        $this->assertTrue($this->summary()['collected']);
        $this->review('ready');
        $this->assertTrue($this->summary()['ready']);
        $this->review('revoke');
        $this->assertFalse($this->summary()['ready']);
        $this->review('ready');
        $this->assertTrue($this->summary()['ready']);
    }

    /**
     * @return void
     */
    public function testParentAndDocumentCannotBeBorrowedFromAnotherCaseOrProject(): void
    {
        $id = $this->createCase();
        $otherId = $this->createCase();
        $events = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $request = $events->newEmptyEntity();
        $request->case_id = $otherId;
        $request->user_id = USER_ADMIN;
        $request->kind = 'request';
        $request->event_date = '2026-10-13';
        $request->sequence = 1;
        $events->saveOrFail($request);
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'supplement',
            'parent_id' => $request->id, 'event_date' => '2026-10-13',
        ]);
        $this->assertResponseOk();
        $this->assertSame(0, $events->find()->where(['case_id' => $id])->count());
        // This document has no project and is therefore not selectable.
        $this->post('/projects/projects-authorities/event/' . $id, [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'kind' => 'opinion',
            'event_date' => '2026-10-13', 'document_ids' => [self::DOCUMENT],
        ]);
        $this->assertResponseOk();
        $this->assertSame(0, $events->find()->where(['case_id' => $id])->count());
    }

    /**
     * @return void
     */
    public function testStaleConfirmationIsRejected(): void
    {
        $snapshot = $this->summary()['snapshotHash'];
        $this->createCase();
        $this->post('/projects/projects-authorities/review/' . self::PROJECT, [
            'kind' => 'ready', 'snapshot_hash' => $snapshot,
        ]);
        $this->assertResponseCode(302);
        $this->assertFlashMessage('Postopek se je spremenil, odkar je bil obrazec odprt. Preverite trenutne podatke in shranite znova.');
        $this->assertSame(0, $this->getTableLocator()->get('Projects.ProjectsAuthorityReviews')->find()->count());
    }

    /**
     * @return void
     */
    public function testNoAccessToAnotherCompanyProjectEvenForAdmin(): void
    {
        $this->getTableLocator()->get('Projects.Projects')->updateAll(
            ['owner_id' => Text::uuid()],
            ['id' => self::PROJECT],
        );
        $this->get('/projects/projects-authorities/index?project=' . self::PROJECT);
        $this->assertResponseCode(403);
    }

    /**
     * @return void
     */
    public function testCommonUserCannotConfirmOrAccessUnassignedProject(): void
    {
        $this->session(['Auth' => $this->getTableLocator()->get('Users')->get(USER_COMMON)]);
        $this->get('/projects/projects-authorities/index?project=' . self::PROJECT);
        $this->assertResponseCode(403);
        $this->post('/projects/projects-authorities/review/4dd53305-9715-4be4-b169-20defe113d2b', ['kind' => 'list']);
        $this->assertResponseCode(403);
    }

    /**
     * @param string $caseId Case ID.
     * @param array<string, mixed> $data Event form data.
     * @return void
     */
    private function postEvent(string $caseId, array $data): void
    {
        $this->post('/projects/projects-authorities/event/' . $caseId, $data + [
            'snapshot_hash' => $this->summary()['snapshotHash'], 'event_date' => '2026-10-13',
        ]);
    }

    /**
     * The receipt date is the response itself and survives approval and later events.
     *
     * @return void
     */
    public function testReceiptDateStaysAfterApprovalAndLaterEvents(): void
    {
        $id = $this->createCase();
        $this->getTableLocator()->get('Documents.Documents')->updateAll([
            'project_id' => self::PROJECT, 'dat_issue' => '2026-09-08',
        ], ['id' => self::DOCUMENT]);
        $this->postEvent($id, ['kind' => 'opinion', 'document_ids' => [self::DOCUMENT]]);
        $response = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents')->find()
            ->where(['case_id' => $id])->firstOrFail();
        $this->postEvent($id, ['kind' => 'approve', 'parent_id' => $response->id]);
        $this->postEvent($id, ['kind' => 'other']);
        $this->get('/projects/projects/view/' . self::PROJECT . '?tab=authorities');
        $this->assertResponseOk();
        $this->assertResponseContains('authority-receipt');
        $this->assertResponseContains('08.09.2026');
        $this->assertResponseContains('Potrjena mnenja: 1 od 1');
    }

    /**
     * A stale form re-renders with the entered data instead of an error page and saves nothing.
     *
     * @return void
     */
    public function testStaleEventFormReRendersWithMessage(): void
    {
        $id = $this->createCase();
        $stale = $this->summary()['snapshotHash'];
        $this->postEvent($id, ['kind' => 'submission']);
        $this->assertResponseCode(302);
        $events = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $this->assertSame(1, $events->find()->where(['case_id' => $id])->count());
        $this->post('/projects/projects-authorities/event/' . $id, [
            'kind' => 'submission', 'event_date' => '2026-10-14', 'descript' => 'Entered text', 'snapshot_hash' => $stale,
        ]);
        $this->assertResponseOk();
        $this->assertResponseContains('Entered text');
        $this->assertResponseContains('Postopek se je spremenil, odkar je bil obrazec odprt.');
        $this->assertSame(1, $events->find()->where(['case_id' => $id])->count());
    }

    /**
     * @return void
     */
    public function testExemptCaseDoesNotBlockReadiness(): void
    {
        $id = $this->createCase();
        $this->postEvent($id, ['kind' => 'request']);
        $this->assertResponseCode(200);
        $this->getTableLocator()->get('Documents.Documents')->updateAll(['project_id' => self::PROJECT], ['id' => self::DOCUMENT]);
        $this->postEvent($id, ['kind' => 'request', 'document_ids' => [self::DOCUMENT]]);
        $this->assertResponseCode(302);
        $this->assertFalse($this->summary()['collected']);
        $this->post('/projects/projects-authorities/edit-case/' . $id, [
            'contact_id' => COMPANY_FIRST, 'title' => 'Elektro', 'exempt_reason' => 'Not connected to the grid',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $summary = $this->summary();
        $this->assertTrue($summary['collected']);
        $this->assertSame(1, $summary['exempt']);
        $this->assertSame('exempt', $summary['rows'][$id]['status']);
    }

    /**
     * @return void
     */
    public function testCaseWithoutHistoryCanBeDeletedButNotWithHistory(): void
    {
        $empty = $this->createCase();
        $used = $this->createCase();
        $this->postEvent($used, ['kind' => 'submission']);
        $cases = $this->getTableLocator()->get('Projects.ProjectsAuthorityCases');
        $this->post('/projects/projects-authorities/delete-case/' . $used);
        $this->assertRedirectContains('/projects/projects-authorities/view/' . $used);
        $this->assertTrue($cases->exists(['id' => $used]));
        $this->get('/projects/projects-authorities/delete-case/' . $empty);
        $this->assertResponseCode(405);
        $links = $this->getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        $link = $links->newEmptyEntity();
        $link->id = Text::uuid();
        $link->case_id = $empty;
        $link->calendar_id = USER_ADMIN;
        $links->saveOrFail($link);
        $this->post('/projects/projects-authorities/delete-case/' . $empty);
        $this->assertResponseCode(302);
        $this->assertFalse($cases->exists(['id' => $empty]));
        $this->assertFalse($links->exists(['case_id' => $empty]));
    }

    /**
     * Saving a project refreshes only the subscriptions to its own cases.
     *
     * @return void
     */
    public function testProjectSaveSynchronizesOnlyItsOwnSubscriptions(): void
    {
        $id = $this->createCase();
        $links = $this->getTableLocator()->get('Projects.ProjectsAuthorityCalendarLinks');
        $own = $links->newEmptyEntity();
        $own->id = Text::uuid();
        $own->case_id = $id;
        $own->calendar_id = USER_ADMIN;
        $links->saveOrFail($own);
        $foreign = $links->newEmptyEntity();
        $foreign->id = Text::uuid();
        // A subscription to a missing case is removed whenever it is synchronized.
        $foreign->case_id = Text::uuid();
        $foreign->calendar_id = USER_ADMIN;
        $links->saveOrFail($foreign);
        $projects = $this->getTableLocator()->get('Projects.Projects');
        $project = $projects->get(self::PROJECT);
        $project->title = 'Renamed';
        $projects->saveOrFail($project);
        $this->assertTrue($links->exists(['id' => $foreign->id]));
        $this->assertTrue($links->exists(['id' => $own->id]));
    }

    /**
     * @return void
     */
    public function testLinkedRecordsCannotBeDeleted(): void
    {
        $id = $this->createCase();
        $documents = $this->getTableLocator()->get('Documents.Documents');
        $documents->updateAll(['project_id' => self::PROJECT], ['id' => self::DOCUMENT]);
        $this->postEvent($id, ['kind' => 'opinion', 'document_ids' => [self::DOCUMENT]]);
        $this->assertResponseCode(302);
        $this->assertFalse($documents->delete($documents->get(self::DOCUMENT)));
        $this->assertTrue($documents->exists(['id' => self::DOCUMENT]));
        $contacts = $this->getTableLocator()->get('Crm.Contacts');
        $this->assertFalse($contacts->delete($contacts->get(COMPANY_FIRST)));
        $projects = $this->getTableLocator()->get('Projects.Projects');
        $this->assertFalse($projects->delete($projects->get(self::PROJECT)));
    }

    /**
     * @return void
     */
    public function testDatesAreValidated(): void
    {
        $id = $this->createCase();
        $events = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $this->postEvent($id, ['kind' => 'submission', 'date_due' => '2026-10-01']);
        $this->assertResponseOk();
        $this->assertSame(0, $events->find()->where(['case_id' => $id])->count());
        $this->postEvent($id, ['kind' => 'submission', 'date_due' => '2026-10-20']);
        $this->assertResponseCode(302);
        $submission = $events->find()->where(['case_id' => $id])->firstOrFail();
        $this->getTableLocator()->get('Documents.Documents')->updateAll(['project_id' => self::PROJECT], ['id' => self::DOCUMENT]);
        $this->postEvent($id, [
            'kind' => 'opinion', 'parent_id' => $submission->id, 'event_date' => '2026-10-01',
            'document_ids' => [self::DOCUMENT],
        ]);
        $this->assertResponseOk();
        $this->assertSame(1, $events->find()->where(['case_id' => $id])->count());
    }

    /**
     * An invalidation can itself be invalidated; the audit log records only what changed.
     *
     * @return void
     */
    public function testInvalidationCanBeUndoneAndEditsLogOnlyChanges(): void
    {
        $id = $this->createCase();
        $events = $this->getTableLocator()->get('Projects.ProjectsAuthorityEvents');
        $this->postEvent($id, ['kind' => 'submission']);
        $submission = $events->find()->where(['case_id' => $id])->firstOrFail();
        $this->postEvent($id, ['kind' => 'void', 'parent_id' => $submission->id, 'descript' => 'Mistake']);
        $this->assertResponseCode(302);
        $void = $events->find()->where(['case_id' => $id, 'kind' => 'void'])->firstOrFail();
        $this->postEvent($id, ['kind' => 'void', 'parent_id' => $void->id, 'descript' => 'Mistake was not a mistake']);
        $this->assertResponseCode(302);
        $workflow = new AuthorityWorkflow();
        $cases = $workflow->cases(self::PROJECT);
        $this->assertCount(1, $workflow->effectiveEvents($cases[0]->events));
        $this->post('/projects/projects-authorities/edit-event/' . $submission->id, [
            'kind' => 'submission', 'event_date' => '2026-10-13', 'descript' => 'Changed note',
            'snapshot_hash' => $this->summary()['snapshotHash'],
        ]);
        $this->assertResponseCode(302);
        $log = $this->getTableLocator()->get('Logs')->find()
            ->where(['descript LIKE' => '%Changed note%'])->firstOrFail();
        $this->assertStringNotContainsString('user_id', $log->descript);
        $this->assertStringNotContainsString('sequence', $log->descript);
    }
}
