<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbService;
use Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException;

class KbServiceTest extends TestCase
{
    private const PROJECT = '4dd53305-9715-4be4-b169-20defe113d2a';

    /**
     * @var array<int, string>
     */
    public array $fixtures = [
        'app.Users',
        'plugin.Projects.Projects',
        'plugin.Projects.ProjectsKbRevisions',
    ];

    private KbService $service;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KbService();
    }

    /**
     * @return \Projects\Model\Entity\Project
     */
    private function project(): object
    {
        return $this->getTableLocator()->get('Projects.Projects')->get(self::PROJECT);
    }

    /**
     * @return \Cake\ORM\Query\SelectQuery
     */
    private function revisions(): object
    {
        return $this->getTableLocator()->get('Projects.ProjectsKbRevisions')->find()
            ->where(['project_id' => self::PROJECT])
            ->orderBy(['version' => 'ASC']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function warning(string $text): array
    {
        return [['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => $text]];
    }

    /**
     * @return void
     */
    public function testFirstWriteConvertsLegacyDescriptionAndKeepsOriginal(): void
    {
        $projects = $this->getTableLocator()->get('Projects.Projects');
        $projects->updateAll(['descript' => 'Stara stanovanjska hiša.'], ['id' => self::PROJECT]);

        $write = $this->service->apply(self::PROJECT, $this->warning('Manjka PGD'), KbOperationApplier::BATCH);

        $this->assertNotNull($write->revision);
        $project = $this->project();
        $this->assertSame(1, $project->kb_format);
        $this->assertSame(2, $project->kb_version);

        $doc = KbDocument::parse($project->descript);
        $this->assertSame('Stara stanovanjska hiša.', $doc->text('description'));
        $this->assertSame('Manjka PGD', $doc->items('warnings')[0]->text);
        $this->assertCount(2, $doc->changelog());
        $this->assertStringContainsString('AI (paket): dodano opozorilo', $doc->changelog()[0]);

        $revisions = $this->revisions()->all()->toList();
        $this->assertSame([0, 1, 2], array_map(fn($r) => $r->version, $revisions));
        $this->assertSame('Stara stanovanjska hiša.', $revisions[0]->descript);
        $this->assertSame($project->descript, $revisions[2]->descript);
    }

    /**
     * @return void
     */
    public function testEmptyLegacyDescriptionStoresNoOriginalRevision(): void
    {
        $this->service->apply(self::PROJECT, $this->warning('A'), KbOperationApplier::BATCH);

        $this->assertSame([1, 2], array_map(fn($r) => $r->version, $this->revisions()->all()->toList()));
    }

    /**
     * @return void
     */
    public function testNothingIsWrittenWhenNoOperationApplies(): void
    {
        $write = $this->service->apply(
            self::PROJECT,
            [['op' => 'remove_item', 'id' => 'w-0000']],
            KbOperationApplier::BATCH,
        );

        $this->assertNull($write->revision);
        $this->assertCount(1, $write->result->rejected);
        $this->assertSame(0, $this->project()->kb_version);
        $this->assertSame(0, $this->revisions()->count());
    }

    /**
     * @return void
     */
    public function testChatChangeIsAttributedToTheUser(): void
    {
        $write = $this->service->apply(
            self::PROJECT,
            $this->warning('Preveri statiko'),
            KbOperationApplier::CHAT,
            USER_ADMIN,
            summary: 'Dodano opozorilo o statiki',
        );

        $doc = KbDocument::parse($this->project()->descript);
        $this->assertStringContainsString('Admin User (pogovor): Dodano opozorilo o statiki', $doc->changelog()[0]);
        $this->assertSame(USER_ADMIN, $write->revision->user_id);
        $this->assertSame('chat', $write->revision->source);
    }

    /**
     * @return void
     */
    public function testStaleVersionIsRefused(): void
    {
        $this->service->apply(self::PROJECT, $this->warning('Prvo'), KbOperationApplier::BATCH);
        $version = (int)$this->project()->kb_version;

        $this->service->apply(self::PROJECT, $this->warning('Drugo'), KbOperationApplier::BATCH, null, $version);

        $this->expectException(StaleKnowledgeBaseException::class);
        $this->service->apply(self::PROJECT, $this->warning('Tretje'), KbOperationApplier::CHAT, null, $version);
    }

    /**
     * @return void
     */
    public function testRefusedWriteLeavesNoTraceOfTheConversion(): void
    {
        $projects = $this->getTableLocator()->get('Projects.Projects');
        $projects->updateAll(['descript' => 'Stara'], ['id' => self::PROJECT]);

        try {
            $this->service->apply(self::PROJECT, $this->warning('A'), KbOperationApplier::CHAT, null, 5);
            $this->fail('Expected a stale version.');
        } catch (StaleKnowledgeBaseException) {
            $project = $this->project();
            $this->assertSame(0, $project->kb_format);
            $this->assertSame('Stara', $project->descript);
            $this->assertSame(0, $this->revisions()->count());
        }
    }

    /**
     * @return void
     */
    public function testManualSaveReplacesSectionsAndKeepsChangelog(): void
    {
        $this->service->apply(self::PROJECT, $this->warning('Prvo'), KbOperationApplier::BATCH);
        $project = $this->project();

        $edited = str_replace('## Povzetek', "## Povzetek\nRočno napisan povzetek", $project->descript);
        $edited = preg_replace('/<!-- kb:section id=changelog -->.*<!-- \/kb:section -->/s', '', $edited);

        $revision = $this->service->saveManual(self::PROJECT, $edited, (int)$project->kb_version, USER_ADMIN);

        $this->assertNotNull($revision);
        $doc = KbDocument::parse($this->project()->descript);
        $this->assertSame('Ročno napisan povzetek', $doc->text('summary'));
        $this->assertSame('Prvo', $doc->items('warnings')[0]->text);
        $this->assertGreaterThanOrEqual(2, count($doc->changelog()));
        $this->assertStringContainsString('Admin User: ročna sprememba: Povzetek', $doc->changelog()[0]);
    }

    /**
     * @return void
     */
    public function testManualSaveWithoutChangeWritesNothing(): void
    {
        $this->service->apply(self::PROJECT, $this->warning('Prvo'), KbOperationApplier::BATCH);
        $project = $this->project();

        $revision = $this->service->saveManual(self::PROJECT, $project->descript, (int)$project->kb_version);

        $this->assertNull($revision);
        $this->assertSame($project->kb_version, $this->project()->kb_version);
    }

    /**
     * @return void
     */
    public function testManualSaveOfFreeTextForLegacyProjectSetsDescription(): void
    {
        $this->getTableLocator()->get('Projects.Projects')
            ->updateAll(['descript' => 'Stara'], ['id' => self::PROJECT]);

        $this->service->saveManual(self::PROJECT, 'Nov opis projekta', 0, USER_ADMIN);

        $doc = KbDocument::parse($this->project()->descript);
        $this->assertSame('Nov opis projekta', $doc->text('description'));
        $this->assertSame('', $doc->text('notes'));
    }

    /**
     * @return void
     */
    public function testManualSaveCannotEmptyTheKnowledgeBase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->saveManual(self::PROJECT, "  \n");
    }

    /**
     * @return void
     */
    public function testRevertRestoresContentAsNewRevisionAndKeepsHistory(): void
    {
        $this->service->apply(self::PROJECT, $this->warning('Prvo'), KbOperationApplier::BATCH);
        $afterFirst = $this->revisions()->orderBy(['version' => 'DESC'])->first();
        $this->service->apply(self::PROJECT, $this->warning('Drugo'), KbOperationApplier::CHAT, USER_ADMIN);

        $revision = $this->service->revert(self::PROJECT, $afterFirst->id, USER_ADMIN);

        $project = $this->project();
        $this->assertSame($revision->version, $project->kb_version);
        $doc = KbDocument::parse($project->descript);
        $this->assertCount(1, $doc->items('warnings'));
        $this->assertSame('Prvo', $doc->items('warnings')[0]->text);
        $this->assertStringContainsString('Admin User (razveljavitev): vrnjeno stanje verzije', $doc->changelog()[0]);
        // The entry about the reverted change is not lost.
        $this->assertStringContainsString('Drugo', implode("\n", array_map(
            fn($r) => (string)$r->operations,
            $this->revisions()->all()->toList(),
        )));
        $this->assertSame('revert', $revision->source);
    }

    /**
     * @return void
     */
    public function testRevertToForeignOrLegacyRevisionIsRefused(): void
    {
        $this->getTableLocator()->get('Projects.Projects')
            ->updateAll(['descript' => 'Stara'], ['id' => self::PROJECT]);
        $this->service->apply(self::PROJECT, $this->warning('A'), KbOperationApplier::BATCH);
        $original = $this->revisions()->where(['version' => 0])->firstOrFail();

        $this->expectException(InvalidArgumentException::class);
        $this->service->revert(self::PROJECT, $original->id);
    }

    /**
     * @return void
     */
    public function testMigrateLegacyIsIdempotent(): void
    {
        $this->getTableLocator()->get('Projects.Projects')
            ->updateAll(['descript' => 'Stara'], ['id' => self::PROJECT]);

        $this->assertTrue($this->service->migrateLegacy(self::PROJECT));
        $this->assertFalse($this->service->migrateLegacy(self::PROJECT));
        $this->assertSame(1, $this->project()->kb_version);
    }
}
