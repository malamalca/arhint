<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Validates operations proposed by AI (or a user) and applies the valid ones to a document.
 *
 * An invalid operation never fails the others; it is left out and its reason is reported. The batch
 * source is the most restricted one: it can neither remove anything nor touch what users wrote.
 */
class KbOperationApplier
{
    public const BATCH = 'batch';
    public const CHAT = 'chat';
    public const MANUAL = 'manual';
    public const BACKFILL = 'backfill';
    public const TOOL = 'tool';
    public const REVERT = 'revert';

    public const OPERATIONS = [
        'set_section', 'append_section', 'add_item', 'update_item', 'resolve_item', 'remove_item',
    ];

    private const MAX_TEXT = 8000;
    private const MAX_OPERATIONS = ['batch' => 30, 'default' => 100];
    private const MAX_BATCH_SET_SECTION = 8;

    /** What the batch run may do. */
    private const BATCH_SET_SECTIONS = ['summary', 'parties', 'procedures'];
    private const BATCH_APPEND_SECTIONS = ['summary', 'parties', 'procedures', 'decisions', 'documents'];
    private const BATCH_OPERATIONS = ['set_section', 'append_section', 'add_item', 'update_item', 'resolve_item'];

    private const ITEM_NAMES = ['warnings' => 'opozorilo', 'deadlines' => 'rok', 'open_questions' => 'vprašanje'];

    /**
     * Apply operations to a document.
     *
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document, changed in place.
     * @param array<int, mixed> $operations Operations as decoded from JSON.
     * @param string $source One of the source constants.
     * @return \Projects\Lib\KnowledgeBase\KbApplyResult
     */
    public function apply(KbDocument $doc, array $operations, string $source): KbApplyResult
    {
        $result = new KbApplyResult();
        $limit = self::MAX_OPERATIONS[$source] ?? self::MAX_OPERATIONS['default'];
        $setSections = 0;

        foreach (array_values($operations) as $index => $operation) {
            $label = sprintf('#%d', $index + 1);
            if ($index >= $limit) {
                $result->rejected[] = "$label: preveč operacij naenkrat (največ $limit)";
                continue;
            }

            try {
                if (!is_array($operation)) {
                    throw new InvalidArgumentException('operacija ni objekt');
                }
                $name = (string)($operation['op'] ?? '');
                $tooManySets = $name === 'set_section' && ++$setSections > self::MAX_BATCH_SET_SECTION;
                if ($source === self::BATCH && $tooManySets) {
                    throw new InvalidArgumentException('preveč zamenjav poglavij naenkrat');
                }
                $result->descriptions[] = $this->applyOne($doc, $name, $operation, $source);
                $result->applied++;
            } catch (InvalidArgumentException $e) {
                $result->rejected[] = $label . ': ' . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param string $name Operation name.
     * @param array<string, mixed> $op Operation.
     * @param string $source Source.
     * @return string Description of what was done.
     */
    private function applyOne(KbDocument $doc, string $name, array $op, string $source): string
    {
        if (!in_array($name, self::OPERATIONS, true)) {
            throw new InvalidArgumentException("neznana operacija '$name'");
        }
        if ($source === self::BATCH && !in_array($name, self::BATCH_OPERATIONS, true)) {
            throw new InvalidArgumentException("operacija '$name' ni dovoljena v paketni obdelavi");
        }

        return match ($name) {
            'set_section' => $this->setSection($doc, $op, $source),
            'append_section' => $this->appendSection($doc, $op, $source),
            'add_item' => $this->addItem($doc, $op),
            'update_item' => $this->updateItem($doc, $op),
            'resolve_item' => $this->resolveItem($doc, $op),
            default => $this->removeItem($doc, $op),
        };
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @param string $source Source.
     * @return string
     */
    private function setSection(KbDocument $doc, array $op, string $source): string
    {
        $section = $this->textSection($op);
        if ($source === self::BATCH && !in_array($section, self::BATCH_SET_SECTIONS, true)) {
            throw new InvalidArgumentException("poglavja '$section' paketna obdelava ne sme zamenjati");
        }
        $doc->setText($section, $this->body($op, true));

        return sprintf('posodobljeno poglavje %s', KbDocument::SECTIONS[$section]);
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @param string $source Source.
     * @return string
     */
    private function appendSection(KbDocument $doc, array $op, string $source): string
    {
        $section = $this->textSection($op);
        if ($source === self::BATCH && !in_array($section, self::BATCH_APPEND_SECTIONS, true)) {
            throw new InvalidArgumentException("v poglavje '$section' paketna obdelava ne sme dodajati");
        }
        $doc->appendText($section, $this->body($op, false));

        return sprintf('dodano v poglavje %s', KbDocument::SECTIONS[$section]);
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @return string
     */
    private function addItem(KbDocument $doc, array $op): string
    {
        $section = (string)($op['section'] ?? '');
        if (!isset(KbDocument::ITEM_SECTIONS[$section])) {
            throw new InvalidArgumentException("poglavje '$section' nima elementov");
        }
        $text = $this->itemText($op);
        foreach ($doc->items($section) as $existing) {
            if ($existing->isOpen() && mb_strtolower($existing->text) === mb_strtolower($text)) {
                throw new InvalidArgumentException("element že obstaja ({$existing->id})");
            }
        }

        $attributes = ['src' => $this->src($op)];
        if ($section === 'deadlines') {
            $attributes['date'] = $this->date($op['date'] ?? null);
        }
        if ($section === 'warnings') {
            $attributes['severity'] = $this->severity($op['severity'] ?? 'medium');
        }
        $item = $doc->addItem($section, $text, $attributes);

        return match ($section) {
            'deadlines' => sprintf('dodan rok %s (%s)', $item->id, $item->date),
            default => sprintf('dodano %s %s', self::ITEM_NAMES[$section], $item->id),
        };
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @return string
     */
    private function updateItem(KbDocument $doc, array $op): string
    {
        $item = $this->existingItem($doc, $op);
        $changed = false;

        if (isset($op['text'])) {
            $item->text = KbDocument::cleanText($this->itemText($op));
            $changed = true;
        }
        if (isset($op['date'])) {
            if ($item->section !== 'deadlines') {
                throw new InvalidArgumentException('datum imajo samo roki');
            }
            $item->date = $this->date($op['date']);
            $changed = true;
        }
        if (isset($op['severity'])) {
            if ($item->section !== 'warnings') {
                throw new InvalidArgumentException('resnost imajo samo opozorila');
            }
            $item->severity = $this->severity($op['severity']);
            $changed = true;
        }
        if (isset($op['status'])) {
            $item->status = $this->status($item, $op['status']);
            $changed = true;
        }
        if (isset($op['src'])) {
            $item->src = array_values(array_unique(array_merge($item->src, $this->src($op))));
            $changed = true;
        }
        if (!$changed) {
            throw new InvalidArgumentException("pri elementu {$item->id} ni nič za spremeniti");
        }

        return sprintf('posodobljen element %s', $item->id);
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @return string
     */
    private function resolveItem(KbDocument $doc, array $op): string
    {
        $item = $this->existingItem($doc, $op);
        $default = $item->section === 'deadlines' ? 'done' : 'resolved';
        $status = isset($op['status']) ? $this->status($item, $op['status']) : $default;
        if ($status === 'open') {
            throw new InvalidArgumentException('za ponovno odprtje uporabi update_item');
        }
        $item->status = $status;

        $note = trim((string)($op['note'] ?? ''));
        if ($note !== '') {
            $item->text = KbDocument::cleanText(rtrim($item->text, '. ') . ' – ' . $note);
        }

        return sprintf('zaprt element %s', $item->id);
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @return string
     */
    private function removeItem(KbDocument $doc, array $op): string
    {
        $item = $this->existingItem($doc, $op);
        $doc->removeItem($item->id);

        return sprintf('odstranjen element %s', $item->id);
    }

    /**
     * @param array<string, mixed> $op Operation.
     * @return string
     */
    private function textSection(array $op): string
    {
        $section = (string)($op['section'] ?? '');
        if (!in_array($section, KbDocument::TEXT_SECTIONS, true)) {
            throw new InvalidArgumentException("poglavja '$section' ni mogoče urejati kot besedila");
        }

        return $section;
    }

    /**
     * @param array<string, mixed> $op Operation.
     * @param bool $allowEmpty Whether an empty text is allowed.
     * @return string
     */
    private function body(array $op, bool $allowEmpty): string
    {
        $text = $op['text'] ?? null;
        if (!is_string($text) || (!$allowEmpty && trim($text) === '')) {
            throw new InvalidArgumentException('manjka besedilo');
        }
        if (mb_strlen($text) > self::MAX_TEXT) {
            throw new InvalidArgumentException('besedilo je predolgo');
        }

        return $text;
    }

    /**
     * @param array<string, mixed> $op Operation.
     * @return string
     */
    private function itemText(array $op): string
    {
        $text = $op['text'] ?? null;
        if (!is_string($text) || trim($text) === '') {
            throw new InvalidArgumentException('manjka besedilo elementa');
        }

        return $text;
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, mixed> $op Operation.
     * @return \Projects\Lib\KnowledgeBase\KbItem
     */
    private function existingItem(KbDocument $doc, array $op): KbItem
    {
        $id = (string)($op['id'] ?? '');
        $item = $doc->findItem($id);
        if ($item === null) {
            throw new InvalidArgumentException("element '$id' ne obstaja");
        }

        return $item;
    }

    /**
     * @param mixed $date Date.
     * @return string
     */
    private function date(mixed $date): string
    {
        $parsed = is_string($date) ? DateTimeImmutable::createFromFormat('!Y-m-d', $date) : false;
        if (!$parsed || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('neveljaven datum (pričakovan Y-m-d)');
        }

        return $date;
    }

    /**
     * @param mixed $severity Severity.
     * @return string
     */
    private function severity(mixed $severity): string
    {
        if (!in_array($severity, KbItem::SEVERITIES, true)) {
            throw new InvalidArgumentException('neveljavna resnost (high, medium, low)');
        }

        return $severity;
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbItem $item Item.
     * @param mixed $status Status.
     * @return string
     */
    private function status(KbItem $item, mixed $status): string
    {
        if (!in_array($status, KbDocument::STATUSES[$item->section], true)) {
            throw new InvalidArgumentException(sprintf(
                "status '%s' ni dovoljen (%s)",
                is_scalar($status) ? (string)$status : gettype($status),
                implode(', ', KbDocument::STATUSES[$item->section]),
            ));
        }

        return $status;
    }

    /**
     * @param array<string, mixed> $op Operation.
     * @return array<int, string>
     */
    private function src(array $op): array
    {
        $src = $op['src'] ?? [];

        return KbDocument::cleanSrc(array_filter(is_array($src) ? $src : [$src], is_string(...)));
    }
}
