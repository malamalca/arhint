<?php
declare(strict_types=1);

namespace Projects\Test\TestCase\Lib\KnowledgeBase;

use Cake\TestSuite\TestCase;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;

class KbOperationApplierTest extends TestCase
{
    private KbOperationApplier $applier;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->applier = new KbOperationApplier();
    }

    /**
     * @return void
     */
    public function testAddUpdateAndResolveItems(): void
    {
        $doc = KbDocument::empty();

        $result = $this->applier->apply($doc, [
            ['op' => 'add_item', 'section' => 'deadlines', 'date' => '2026-11-15', 'text' => 'Dopolnitev', 'src' => ['log:1']],
            ['op' => 'add_item', 'section' => 'warnings', 'severity' => 'high', 'text' => 'Manjka PGD'],
        ], KbOperationApplier::BATCH);

        $this->assertSame(2, $result->applied);
        $this->assertSame([], $result->rejected);
        $deadline = $doc->items('deadlines')[0];
        $warning = $doc->items('warnings')[0];

        $result = $this->applier->apply($doc, [
            ['op' => 'update_item', 'id' => $deadline->id, 'date' => '2026-11-20'],
            ['op' => 'resolve_item', 'id' => $warning->id, 'note' => 'PGD prejet'],
            ['op' => 'resolve_item', 'id' => $deadline->id],
        ], KbOperationApplier::BATCH);

        $this->assertSame(3, $result->applied);
        $this->assertSame('2026-11-20', $deadline->date);
        $this->assertSame('resolved', $warning->status);
        $this->assertSame('Manjka PGD – PGD prejet', $warning->text);
        $this->assertSame('done', $deadline->status);
    }

    /**
     * @return void
     */
    public function testBatchCannotRemoveOrTouchUserSections(): void
    {
        $doc = KbDocument::empty();
        $item = $doc->addItem('warnings', 'Obstoječe');
        $doc->setText('notes', 'moje opombe');
        $doc->setText('description', 'moj opis');

        $result = $this->applier->apply($doc, [
            ['op' => 'remove_item', 'id' => $item->id],
            ['op' => 'set_section', 'section' => 'notes', 'text' => 'AI'],
            ['op' => 'set_section', 'section' => 'description', 'text' => 'AI'],
            ['op' => 'append_section', 'section' => 'notes', 'text' => 'AI'],
            ['op' => 'set_section', 'section' => 'summary', 'text' => 'Novo stanje'],
        ], KbOperationApplier::BATCH);

        $this->assertSame(1, $result->applied);
        $this->assertCount(4, $result->rejected);
        $this->assertNotNull($doc->findItem($item->id));
        $this->assertSame('moje opombe', $doc->text('notes'));
        $this->assertSame('moj opis', $doc->text('description'));
        $this->assertSame('Novo stanje', $doc->text('summary'));
    }

    /**
     * @return void
     */
    public function testChatMayRemoveAndEditEverySection(): void
    {
        $doc = KbDocument::empty();
        $item = $doc->addItem('open_questions', 'Vprašanje');

        $result = $this->applier->apply($doc, [
            ['op' => 'remove_item', 'id' => $item->id],
            ['op' => 'set_section', 'section' => 'notes', 'text' => 'Nove opombe'],
        ], KbOperationApplier::CHAT);

        $this->assertSame(2, $result->applied);
        $this->assertNull($doc->findItem($item->id));
        $this->assertSame('Nove opombe', $doc->text('notes'));
    }

    /**
     * @return void
     */
    public function testInvalidOperationsAreRejectedWithoutStoppingOthers(): void
    {
        $doc = KbDocument::empty();

        $result = $this->applier->apply($doc, [
            'ni objekt',
            ['op' => 'explode'],
            ['op' => 'add_item', 'section' => 'deadlines', 'text' => 'Brez datuma'],
            ['op' => 'add_item', 'section' => 'deadlines', 'date' => '2026-02-30', 'text' => 'Napačen datum'],
            ['op' => 'add_item', 'section' => 'summary', 'text' => 'Napačno poglavje'],
            ['op' => 'add_item', 'section' => 'warnings', 'severity' => 'huge', 'text' => 'Resnost'],
            ['op' => 'update_item', 'id' => 'w-0000', 'text' => 'Ne obstaja'],
            ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Veljavno'],
        ], KbOperationApplier::CHAT);

        $this->assertSame(1, $result->applied);
        $this->assertCount(7, $result->rejected);
        $this->assertCount(1, $doc->items('warnings'));
    }

    /**
     * @return void
     */
    public function testDuplicateOpenItemIsRejected(): void
    {
        $doc = KbDocument::empty();
        $op = ['op' => 'add_item', 'section' => 'warnings', 'text' => 'Manjka PGD'];

        $this->applier->apply($doc, [$op], KbOperationApplier::BATCH);
        $result = $this->applier->apply($doc, [array_merge($op, ['text' => 'manjka pgd'])], KbOperationApplier::BATCH);

        $this->assertSame(0, $result->applied);
        $this->assertCount(1, $doc->items('warnings'));
    }

    /**
     * @return void
     */
    public function testStatusMustFitTheSection(): void
    {
        $doc = KbDocument::empty();
        $warning = $doc->addItem('warnings', 'A');
        $deadline = $doc->addItem('deadlines', 'B', ['date' => '2026-11-01']);

        $result = $this->applier->apply($doc, [
            ['op' => 'update_item', 'id' => $warning->id, 'status' => 'done'],
            ['op' => 'update_item', 'id' => $warning->id, 'date' => '2026-01-01'],
            ['op' => 'update_item', 'id' => $deadline->id, 'severity' => 'high'],
            ['op' => 'update_item', 'id' => $deadline->id, 'status' => 'cancelled'],
            ['op' => 'resolve_item', 'id' => $warning->id, 'status' => 'open'],
        ], KbOperationApplier::CHAT);

        $this->assertSame(1, $result->applied);
        $this->assertSame('cancelled', $deadline->status);
        $this->assertSame('open', $warning->status);
    }

    /**
     * @return void
     */
    public function testBatchLimitsNumberOfOperations(): void
    {
        $doc = KbDocument::empty();
        $operations = [];
        for ($i = 0; $i < 35; $i++) {
            $operations[] = ['op' => 'add_item', 'section' => 'open_questions', 'text' => "Vprašanje $i"];
        }

        $result = $this->applier->apply($doc, $operations, KbOperationApplier::BATCH);

        $this->assertSame(30, $result->applied);
        $this->assertCount(5, $result->rejected);
    }
}
