<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\TestSuite\TestCase;
use InvalidArgumentException;
use Projects\Lib\KnowledgeBase\KbDocument;

class KbDocumentTest extends TestCase
{
    /**
     * @return void
     */
    public function testEmptyDocumentHasEverySectionAndRoundTrips(): void
    {
        $markdown = KbDocument::empty()->toMarkdown();

        $this->assertTrue(KbDocument::isKnowledgeBase($markdown));
        foreach (KbDocument::SECTIONS as $id => $title) {
            $this->assertStringContainsString("<!-- kb:section id=$id -->\n## $title", $markdown);
        }
        $this->assertSame($markdown, KbDocument::parse($markdown)->toMarkdown());
    }

    /**
     * @return void
     */
    public function testContentSurvivesRoundTrip(): void
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', "Gradnja poteka po načrtu.\n\n- točka 1\n- točka 2");
        $doc->addItem('warnings', 'Manjka geomehansko poročilo', ['severity' => 'high', 'src' => ['log:6b1e']]);
        $doc->addItem('deadlines', 'Dopolnitev vloge', ['date' => '2026-11-15', 'src' => ['authority_event:a1']]);
        $doc->addItem('open_questions', 'Kdo plača priključek?');
        $doc->addChangelog('25.10.2026 14:30 · AI (paket): dodan rok');

        $markdown = $doc->toMarkdown();
        $parsed = KbDocument::parse($markdown);

        $this->assertSame($markdown, $parsed->toMarkdown());
        $this->assertSame("Gradnja poteka po načrtu.\n\n- točka 1\n- točka 2", $parsed->text('summary'));
        $warning = $parsed->items('warnings')[0];
        $this->assertSame('Manjka geomehansko poročilo', $warning->text);
        $this->assertSame('high', $warning->severity);
        $this->assertSame(['log:6b1e'], $warning->src);
        $deadline = $parsed->items('deadlines')[0];
        $this->assertSame('2026-11-15', $deadline->date);
        $this->assertSame('Dopolnitev vloge', $deadline->text);
        $this->assertSame(['25.10.2026 14:30 · AI (paket): dodan rok'], $parsed->changelog());
    }

    /**
     * @return void
     */
    public function testItemIdsAreKeptAndAreUnique(): void
    {
        $doc = KbDocument::empty();
        $first = $doc->addItem('warnings', 'Prvo');
        $second = $doc->addItem('warnings', 'Drugo');

        $this->assertNotSame($first->id, $second->id);
        $this->assertMatchesRegularExpression('/^w-[0-9a-f]{4}$/', $first->id);

        $parsed = KbDocument::parse($doc->toMarkdown());
        $this->assertSame([$first->id, $second->id], array_map(fn($i) => $i->id, $parsed->items('warnings')));
    }

    /**
     * @return void
     */
    public function testManualEditOfVisiblePartWins(): void
    {
        $doc = KbDocument::empty();
        $deadline = $doc->addItem('deadlines', 'Oddaja', ['date' => '2026-11-15']);
        $warning = $doc->addItem('warnings', 'Pozno', ['severity' => 'low']);

        $markdown = $doc->toMarkdown();
        $markdown = str_replace('**15.11.2026**', '**20.11.2026**', $markdown);
        $markdown = str_replace('⏳', '✅', $markdown);
        $markdown = str_replace('🟢', '🔴', $markdown);

        $parsed = KbDocument::parse($markdown);
        $this->assertSame('2026-11-20', $parsed->findItem($deadline->id)->date);
        $this->assertSame('done', $parsed->findItem($deadline->id)->status);
        $this->assertSame('high', $parsed->findItem($warning->id)->severity);
    }

    /**
     * @return void
     */
    public function testHandAddedBulletGetsAnIdAndStrayTextGoesToNotes(): void
    {
        $markdown = KbDocument::empty()->toMarkdown();
        $markdown = str_replace(
            "## Opozorila\n<!-- /kb:section -->",
            "## Opozorila\n- 🟡 Ročno dodano opozorilo\nPojasnilo brez alineje\n<!-- /kb:section -->",
            $markdown,
        );
        $markdown .= "\nBesedilo izven poglavij\n";

        $parsed = KbDocument::parse($markdown);

        $warnings = $parsed->items('warnings');
        $this->assertCount(1, $warnings);
        $this->assertSame('Ročno dodano opozorilo', $warnings[0]->text);
        $this->assertMatchesRegularExpression('/^w-[0-9a-f]{4}$/', $warnings[0]->id);
        $this->assertStringContainsString('Pojasnilo brez alineje', $parsed->text('notes'));
        $this->assertStringContainsString('Besedilo izven poglavij', $parsed->text('notes'));
    }

    /**
     * @return void
     */
    public function testTocAndChangelogAreGenerated(): void
    {
        $doc = KbDocument::empty();
        $doc->addItem('warnings', 'A');
        $doc->addItem('warnings', 'B');
        $closed = $doc->addItem('warnings', 'C');
        $closed->status = 'resolved';

        $markdown = $doc->toMarkdown();
        $this->assertStringContainsString('- [Opozorila](#kb-opozorila) (2 odprtih)', $markdown);

        for ($i = 1; $i <= 60; $i++) {
            $doc->addChangelog("vnos $i");
        }
        $this->assertCount(50, $doc->changelog());
        $this->assertSame('vnos 60', $doc->changelog()[0]);
    }

    /**
     * @return void
     */
    public function testDeadlinesAreOrderedOpenFirstThenByDate(): void
    {
        $doc = KbDocument::empty();
        $late = $doc->addItem('deadlines', 'Pozni', ['date' => '2026-12-01']);
        $done = $doc->addItem('deadlines', 'Končan', ['date' => '2026-01-01', 'status' => 'done']);
        $early = $doc->addItem('deadlines', 'Zgodnji', ['date' => '2026-11-01']);

        $this->assertSame(
            [$early->id, $late->id, $done->id],
            array_map(fn($item) => $item->id, $doc->items('deadlines')),
        );
    }

    /**
     * @return void
     */
    public function testLegacyDescriptionBecomesDescriptionSection(): void
    {
        $doc = KbDocument::fromLegacy("  Stara stanovanjska hiša.\n\nVeč vrstic.  ");

        $this->assertSame("Stara stanovanjska hiša.\n\nVeč vrstic.", $doc->text('description'));
        $this->assertFalse(KbDocument::isKnowledgeBase('Stara stanovanjska hiša.'));
    }

    /**
     * @return void
     */
    public function testUnknownSectionIsKept(): void
    {
        $markdown = KbDocument::empty()->toMarkdown();
        $markdown = str_replace(
            '<!-- kb:section id=changelog -->',
            "<!-- kb:section id=extra -->\n## Dodatno\nvsebina\n<!-- /kb:section -->\n\n<!-- kb:section id=changelog -->",
            $markdown,
        );

        $parsed = KbDocument::parse($markdown);

        $this->assertStringContainsString("<!-- kb:section id=extra -->\n## Dodatno\nvsebina", $parsed->toMarkdown());
    }

    /**
     * @return void
     */
    public function testStructureCommentsInTextAreRemoved(): void
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', "Pred <!-- /kb:section --> po\n<!-- kb:section id=toc -->");

        $this->assertStringNotContainsString('kb:section', $doc->text('summary'));
        $this->assertSame($doc->toMarkdown(), KbDocument::parse($doc->toMarkdown())->toMarkdown());
    }

    /**
     * @return void
     */
    public function testItemTextIsOneLineAndCannotBreakComments(): void
    {
        $doc = KbDocument::empty();
        $doc->addItem('warnings', "Prva vrstica\nDruga --> vrstica <!-- kb:item id=x -->");

        $parsed = KbDocument::parse($doc->toMarkdown());

        $this->assertCount(1, $parsed->items('warnings'));
        $this->assertStringNotContainsString("\n", $parsed->items('warnings')[0]->text);
    }

    /**
     * @return void
     */
    public function testWrongSectionTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        KbDocument::empty()->setText('warnings', 'x');
    }
}
