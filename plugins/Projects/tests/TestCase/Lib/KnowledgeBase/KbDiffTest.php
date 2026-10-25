<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\TestSuite\TestCase;
use Projects\Lib\KnowledgeBase\KbDiff;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbRenderer;

class KbDiffTest extends TestCase
{
    /**
     * @return void
     */
    public function testShowsAddedAndRemovedLinesButNotStructure(): void
    {
        $old = KbDocument::empty();
        $old->setText('summary', 'Prvi povzetek');
        $warning = $old->addItem('warnings', 'Staro opozorilo');

        $new = KbDocument::parse($old->toMarkdown());
        $new->setText('summary', 'Drugi povzetek');
        $new->removeItem($warning->id);
        $new->addItem('warnings', 'Novo opozorilo');

        $diff = (new KbDiff())->diff($old->toMarkdown(), $new->toMarkdown());

        $this->assertContains(['removed', 'Prvi povzetek'], $diff);
        $this->assertContains(['added', 'Drugi povzetek'], $diff);
        $this->assertContains(['removed', '- 🟡 Staro opozorilo'], $diff);
        $this->assertContains(['added', '- 🟡 Novo opozorilo'], $diff);
        $this->assertContains(['same', '## Povzetek'], $diff);
        foreach ($diff as [, $line]) {
            $this->assertStringNotContainsString('kb:', $line);
            $this->assertStringNotContainsString('Kazalo', $line);
        }
    }

    /**
     * @return void
     */
    public function testFirstVersionIsAllAdded(): void
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', 'Povzetek');

        $diff = (new KbDiff())->diff(null, $doc->toMarkdown());

        $this->assertNotSame([], $diff);
        $this->assertSame([], array_filter($diff, fn(array $line): bool => $line[0] !== 'added'));
    }

    /**
     * @return void
     */
    public function testIdenticalStatesHaveNoChanges(): void
    {
        $markdown = KbDocument::empty()->toMarkdown();

        $diff = (new KbDiff())->diff($markdown, $markdown);

        $this->assertSame([], array_filter($diff, fn(array $line): bool => $line[0] !== 'same'));
    }

    /**
     * @return void
     */
    public function testDisplayLeavesOutEmptySectionsAndComments(): void
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', 'Povzetek stanja');
        $doc->addItem('deadlines', 'Oddaja', ['date' => '2026-11-15']);

        $markdown = $doc->toDisplayMarkdown();

        $this->assertStringContainsString("## Povzetek\nPovzetek stanja", $markdown);
        $this->assertStringContainsString('- ⏳ **15.11.2026** – Oddaja', $markdown);
        $this->assertStringNotContainsString('Udeleženci', $markdown);
        $this->assertStringNotContainsString('kb:', $markdown);
        $this->assertSame('', KbDocument::empty()->toDisplayMarkdown());
    }

    /**
     * @return void
     */
    public function testRendererLinksTocToHeadingsAndStripsHtml(): void
    {
        $doc = KbDocument::empty();
        $doc->setText('summary', 'Pozor <script>alert(1)</script> besedilo');
        $doc->addItem('warnings', 'Manjka PGD');

        $html = (new KbRenderer())->render($doc);

        $this->assertStringContainsString('<h2 id="kb-opozorila">Opozorila</h2>', $html);
        $this->assertStringContainsString('href="#kb-opozorila"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}
