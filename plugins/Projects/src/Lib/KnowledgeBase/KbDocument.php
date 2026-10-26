<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Markdown knowledge base of a project, split into fixed sections.
 *
 * The markdown stays readable on its own; sections are fenced with HTML comments that the markdown
 * renderer drops, and items of the warnings, deadlines and open questions sections carry their
 * attributes in a trailing comment. What is visible in an item line (marker, date, text) wins when the
 * document is parsed again, so the document can be edited by hand.
 */
class KbDocument
{
    public const MARKER = '<!-- kb:v1 -->';

    public const TOC = 'toc';
    public const CHANGELOG = 'changelog';
    public const STATUS = 'status';

    /** Longest status of the project, in characters; it is shown on the cards of the dashboard. */
    public const STATUS_MAX_CHARS = 180;

    /** Section id => heading. The order is the order in the document. */
    public const SECTIONS = [
        'toc' => 'Kazalo',
        'status' => 'Stanje',
        'summary' => 'Povzetek',
        'description' => 'Opis projekta',
        'warnings' => 'Opozorila',
        'deadlines' => 'Roki',
        'parties' => 'Udeleženci',
        'procedures' => 'Postopki in soglasja',
        'decisions' => 'Odločitve',
        'open_questions' => 'Odprta vprašanja',
        'documents' => 'Ključni dokumenti',
        'notes' => 'Opombe',
        'changelog' => 'Changelog',
    ];

    /** Sections made of items, with the prefix of their ids. */
    public const ITEM_SECTIONS = [
        'warnings' => 'w',
        'deadlines' => 'd',
        'open_questions' => 'q',
    ];

    /** Allowed statuses per item section; the first one is the initial status. */
    public const STATUSES = [
        'warnings' => ['open', 'resolved'],
        'deadlines' => ['open', 'done', 'cancelled'],
        'open_questions' => ['open', 'resolved'],
    ];

    /** Sections holding free markdown. */
    public const TEXT_SECTIONS = [
        'status', 'summary', 'description', 'parties', 'procedures', 'decisions', 'documents', 'notes',
    ];

    private const SEVERITY_MARKERS = ['high' => '🔴', 'medium' => '🟡', 'low' => '🟢'];
    private const STATUS_MARKERS = [
        'warnings' => ['resolved' => '✅'],
        'deadlines' => ['open' => '⏳', 'done' => '✅', 'cancelled' => '❌'],
        'open_questions' => ['open' => '❓', 'resolved' => '✅'],
    ];

    public const MAX_ITEM_TEXT = 1000;

    /** Prefix of heading ids in the rendered document, see KbRenderer. */
    public const ANCHOR_PREFIX = 'kb-';

    /**
     * @var array<string, string> Bodies of the text sections.
     */
    private array $text = [];

    /**
     * @var array<string, array<int, \Projects\Lib\KnowledgeBase\KbItem>>
     */
    private array $items = [];

    /**
     * @var array<int, string> Changelog lines, newest first.
     */
    private array $changelog = [];

    /**
     * @var array<string, string> Sections of an unknown id, kept verbatim.
     */
    private array $unknown = [];

    /**
     * An empty document with every section.
     *
     * @return self
     */
    public static function empty(): self
    {
        $doc = new self();
        foreach (self::TEXT_SECTIONS as $id) {
            $doc->text[$id] = '';
        }
        foreach (array_keys(self::ITEM_SECTIONS) as $id) {
            $doc->items[$id] = [];
        }

        return $doc;
    }

    /**
     * Whether the text is a knowledge base document.
     *
     * @param string|null $markdown Text of the description field.
     * @return bool
     */
    public static function isKnowledgeBase(?string $markdown): bool
    {
        return $markdown !== null && str_contains($markdown, self::MARKER);
    }

    /**
     * A document that keeps a free-form description as its description section.
     *
     * @param string $description The former description.
     * @return self
     */
    public static function fromLegacy(string $description): self
    {
        $doc = self::empty();
        $doc->text['description'] = trim($description);

        return $doc;
    }

    /**
     * Parse markdown. Anything outside the sections ends up in the notes section, missing sections are added.
     *
     * @param string $markdown Markdown.
     * @return self
     */
    public static function parse(string $markdown): self
    {
        $doc = self::empty();
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $pattern = '/<!-- kb:section id=([a-z_]+) -->\n?(.*?)\n?<!-- \/kb:section -->/s';

        $stray = trim((string)preg_replace($pattern, '', str_replace(self::MARKER, '', $markdown)));

        preg_match_all($pattern, $markdown, $blocks, PREG_SET_ORDER);
        foreach ($blocks as [$block, $id, $body]) {
            if (!isset(self::SECTIONS[$id])) {
                $doc->unknown[$id] = trim($block);
                continue;
            }
            $body = self::stripHeading($body, $id);

            if ($id === self::TOC) {
                continue;
            }
            if ($id === self::CHANGELOG) {
                $doc->changelog = self::parseChangelog($body);
            } elseif (isset(self::ITEM_SECTIONS[$id])) {
                $rest = $doc->parseItems($id, $body);
                if ($rest !== '') {
                    $stray = trim($stray . "\n\n" . $rest);
                }
            } else {
                $doc->text[$id] = self::cleanBody($body);
            }
        }

        if ($stray !== '') {
            $doc->text['notes'] = trim($doc->text['notes'] . "\n\n" . $stray);
        }

        return $doc;
    }

    /**
     * Body of a text section.
     *
     * @param string $id Section id.
     * @return string
     */
    public function text(string $id): string
    {
        $this->assertTextSection($id);

        return $this->text[$id];
    }

    /**
     * Replace the body of a text section.
     *
     * @param string $id Section id.
     * @param string $body New body.
     * @return void
     */
    public function setText(string $id, string $body): void
    {
        $this->assertTextSection($id);
        $this->text[$id] = self::cleanBody($body);
    }

    /**
     * Add to the body of a text section.
     *
     * @param string $id Section id.
     * @param string $body Markdown to add.
     * @return void
     */
    public function appendText(string $id, string $body): void
    {
        $this->assertTextSection($id);
        $this->text[$id] = trim($this->text[$id] . "\n" . self::cleanBody($body));
    }

    /**
     * Items of a section, deadlines ordered by date.
     *
     * @param string $section Section id.
     * @return array<int, \Projects\Lib\KnowledgeBase\KbItem>
     */
    public function items(string $section): array
    {
        $this->assertItemSection($section);
        $items = $this->items[$section];
        if ($section === 'deadlines') {
            usort($items, fn(KbItem $a, KbItem $b): int => [$a->isOpen() ? 0 : 1, (string)$a->date]
                <=> [$b->isOpen() ? 0 : 1, (string)$b->date]);
        }

        return array_values($items);
    }

    /**
     * Find an item by id in any section.
     *
     * @param string $id Item id.
     * @return \Projects\Lib\KnowledgeBase\KbItem|null
     */
    public function findItem(string $id): ?KbItem
    {
        foreach ($this->items as $items) {
            foreach ($items as $item) {
                if ($item->id === $id) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Add a new item with a freshly generated id.
     *
     * @param string $section Item section.
     * @param string $text Text.
     * @param array<string, mixed> $attributes date, severity, status, src.
     * @return \Projects\Lib\KnowledgeBase\KbItem
     */
    public function addItem(string $section, string $text, array $attributes = []): KbItem
    {
        $this->assertItemSection($section);
        $item = new KbItem(
            $this->newItemId($section),
            $section,
            self::cleanText($text),
            $attributes['date'] ?? null,
            $attributes['severity'] ?? 'medium',
            $attributes['status'] ?? self::STATUSES[$section][0],
            self::cleanSrc((array)($attributes['src'] ?? [])),
        );
        $this->items[$section][] = $item;

        return $item;
    }

    /**
     * Remove an item.
     *
     * @param string $id Item id.
     * @return bool False when there is no such item.
     */
    public function removeItem(string $id): bool
    {
        foreach ($this->items as $section => $items) {
            foreach ($items as $index => $item) {
                if ($item->id === $id) {
                    unset($this->items[$section][$index]);
                    $this->items[$section] = array_values($this->items[$section]);

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Changelog lines, newest first.
     *
     * @return array<int, string>
     */
    public function changelog(): array
    {
        return $this->changelog;
    }

    /**
     * Replace the changelog.
     *
     * @param array<int, string> $lines Lines, newest first.
     * @return void
     */
    public function setChangelog(array $lines): void
    {
        $this->changelog = array_values(array_map(self::cleanLine(...), $lines));
    }

    /**
     * Add a line on top of the changelog and drop the oldest ones.
     *
     * @param string $line Changelog entry without the leading dash.
     * @param int $max Number of entries to keep.
     * @return void
     */
    public function addChangelog(string $line, int $max = 50): void
    {
        array_unshift($this->changelog, self::cleanLine($line));
        $this->changelog = array_slice($this->changelog, 0, $max);
    }

    /**
     * Number of open items per item section.
     *
     * @return array<string, int>
     */
    public function openCounts(): array
    {
        $counts = [];
        foreach ($this->items as $section => $items) {
            $counts[$section] = count(array_filter($items, fn(KbItem $item): bool => $item->isOpen()));
        }

        return $counts;
    }

    /**
     * Markdown of every section that can be edited, to find out what a change touched.
     *
     * @return array<string, string>
     */
    public function renderedSections(): array
    {
        $rendered = [];
        foreach (self::SECTIONS as $id => $title) {
            if ($id !== self::TOC && $id !== self::CHANGELOG) {
                $rendered[$id] = $this->renderSection($id, $title);
            }
        }

        return $rendered;
    }

    /**
     * Build the markdown, with a regenerated table of contents.
     *
     * @return string
     */
    public function toMarkdown(): string
    {
        $blocks = [self::MARKER];
        foreach (self::SECTIONS as $id => $title) {
            if ($id === self::CHANGELOG) {
                foreach ($this->unknown as $block) {
                    $blocks[] = $block;
                }
            }
            $blocks[] = $this->renderSection($id, $title);
        }

        return implode("\n\n", $blocks) . "\n";
    }

    /**
     * Markdown for reading: no structure comments and no empty sections.
     *
     * @return string
     */
    public function toDisplayMarkdown(): string
    {
        $blocks = $this->displayBlocks();

        return $blocks === [] ? '' : implode("\n\n", $blocks) . "\n";
    }

    /**
     * The sections for reading, one markdown block each: the table of contents first, then the sections that
     * are not empty. Nothing at all for an empty document.
     *
     * @return array<string, string> Markdown by section id.
     */
    public function displayBlocks(): array
    {
        $shown = [];
        foreach (self::SECTIONS as $id => $title) {
            if ($id !== self::TOC && $this->sectionBody($id, true) !== '') {
                $shown[$id] = $title;
            }
        }
        if ($shown === []) {
            return [];
        }

        $blocks = [self::TOC => "## Kazalo\n" . $this->renderToc($shown)];
        foreach ($shown as $id => $title) {
            $blocks[$id] = "## $title\n" . $this->sectionBody($id, true);
        }

        return $blocks;
    }

    /**
     * @param string $id Section id.
     * @param bool $display Leave out what is meant for the parser only.
     * @return string
     */
    private function sectionBody(string $id, bool $display = false): string
    {
        if ($id === self::TOC) {
            return $this->renderToc(self::SECTIONS);
        }
        if ($id === self::CHANGELOG) {
            return implode("\n", array_map(fn(string $line): string => '- ' . $line, $this->changelog));
        }
        if (isset(self::ITEM_SECTIONS[$id])) {
            return implode("\n", array_map(
                fn(KbItem $item): string => $this->renderItem($item, $display),
                $this->items($id),
            ));
        }

        return $this->text[$id];
    }

    /**
     * @param string $id Section id.
     * @param string $title Heading.
     * @return string
     */
    private function renderSection(string $id, string $title): string
    {
        $body = $this->sectionBody($id);

        return "<!-- kb:section id=$id -->\n## $title" . ($body === '' ? '' : "\n" . $body) . "\n<!-- /kb:section -->";
    }

    /**
     * @param array<string, string> $sections Sections to list, id => heading.
     * @return string
     */
    private function renderToc(array $sections): string
    {
        $open = $this->openCounts();
        $lines = [];
        foreach ($sections as $id => $title) {
            if ($id === self::TOC) {
                continue;
            }
            $suffix = !empty($open[$id]) ? sprintf(' (%d odprtih)', $open[$id]) : '';
            $lines[] = sprintf('- [%s](#%s)%s', $title, self::anchor($title), $suffix);
        }

        return implode("\n", $lines);
    }

    /**
     * @param string $title Heading.
     * @return string
     */
    private static function anchor(string $title): string
    {
        $slug = trim((string)preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title)), '-');

        return self::ANCHOR_PREFIX . $slug;
    }

    /**
     * @param \Projects\Lib\KnowledgeBase\KbItem $item Item.
     * @param bool $display Leave out the attributes comment.
     * @return string
     */
    private function renderItem(KbItem $item, bool $display = false): string
    {
        $statusMarker = self::STATUS_MARKERS[$item->section][$item->status] ?? null;
        $visible = match ($item->section) {
            'warnings' => ($statusMarker ?? self::SEVERITY_MARKERS[$item->severity]) . ' ' . $item->text,
            'deadlines' => $statusMarker . ' **' . $this->displayDate($item->date) . '** – ' . $item->text,
            default => ($statusMarker ?? '❓') . ' ' . $item->text,
        };

        if ($display) {
            return '- ' . $visible;
        }

        $attributes = ['id' => $item->id, 'status' => $item->status];
        if ($item->section === 'warnings') {
            $attributes['sev'] = $item->severity;
        }
        if ($item->date !== null) {
            $attributes['date'] = $item->date;
        }
        if ($item->src !== []) {
            $attributes['src'] = implode(',', $item->src);
        }
        $pairs = [];
        foreach ($attributes as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return sprintf('- %s <!-- kb:item %s -->', $visible, implode(' ', $pairs));
    }

    /**
     * @param string|null $date Y-m-d date.
     * @return string
     */
    private function displayDate(?string $date): string
    {
        $parsed = $date === null ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed ? $parsed->format('d.m.Y') : '??.??.????';
    }

    /**
     * Parse the lines of an item section.
     *
     * @param string $section Section id.
     * @param string $body Body of the section.
     * @return string Lines that are not items.
     */
    private function parseItems(string $section, string $body): string
    {
        $stray = [];
        foreach (explode("\n", trim($body)) as $line) {
            $line = rtrim($line);
            if ($line === '') {
                continue;
            }
            if (!preg_match('/^[-*] (.*)$/', $line, $match)) {
                $stray[] = $line;
                continue;
            }

            $attributes = [];
            $visible = trim((string)preg_replace_callback(
                '/\s*<!-- kb:item (.*?) -->\s*$/',
                function (array $found) use (&$attributes): string {
                    foreach (explode(' ', trim($found[1])) as $pair) {
                        [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
                        $attributes[$key] = $value;
                    }

                    return '';
                },
                $match[1],
            ));
            if ($visible === '') {
                continue;
            }

            $this->items[$section][] = $this->itemFromLine($section, $visible, $attributes);
        }

        return implode("\n", $stray);
    }

    /**
     * @param string $section Section id.
     * @param string $visible Visible part of the line.
     * @param array<string, string> $attributes Attributes of the trailing comment.
     * @return \Projects\Lib\KnowledgeBase\KbItem
     */
    private function itemFromLine(string $section, string $visible, array $attributes): KbItem
    {
        $statuses = self::STATUSES[$section];
        $status = in_array($attributes['status'] ?? '', $statuses, true) ? $attributes['status'] : $statuses[0];
        $severity = in_array($attributes['sev'] ?? '', KbItem::SEVERITIES, true) ? $attributes['sev'] : 'medium';
        $date = $this->validDate($attributes['date'] ?? null);

        // The visible marker wins over the attribute, so a hand edit of the marker takes effect.
        if (preg_match('/^(\S+) (.*)$/us', $visible, $parts)) {
            $marker = $parts[1];
            $statusMarkers = array_flip(self::STATUS_MARKERS[$section]);
            $severityMarkers = array_flip(self::SEVERITY_MARKERS);
            if (isset($statusMarkers[$marker])) {
                $status = $statusMarkers[$marker];
                $visible = $parts[2];
            } elseif ($section === 'warnings' && isset($severityMarkers[$marker])) {
                $severity = $severityMarkers[$marker];
                $status = 'open';
                $visible = $parts[2];
            } elseif ($section === 'open_questions' && $marker === '❓') {
                $status = 'open';
                $visible = $parts[2];
            }
        }

        $deadlinePattern = '/^\*\*(\d{1,2})\.(\d{1,2})\.(\d{4})\*\* – (.*)$/us';
        if ($section === 'deadlines' && preg_match($deadlinePattern, $visible, $parts)) {
            $date = $this->validDate(sprintf('%04d-%02d-%02d', $parts[3], $parts[2], $parts[1])) ?? $date;
            $visible = $parts[4];
        }

        $id = $attributes['id'] ?? '';
        $prefix = self::ITEM_SECTIONS[$section] . '-';
        if (!preg_match('/^' . $prefix . '[0-9a-f]{4,}$/', $id) || $this->findItem($id) !== null) {
            $id = $this->newItemId($section);
        }

        return new KbItem(
            $id,
            $section,
            self::cleanText($visible),
            $section === 'deadlines' ? $date : null,
            $severity,
            $status,
            self::cleanSrc(explode(',', $attributes['src'] ?? '')),
        );
    }

    /**
     * @param string|null $date Y-m-d date.
     * @return string|null The date when valid.
     */
    private function validDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date ? $date : null;
    }

    /**
     * @param string $section Item section.
     * @return string
     */
    private function newItemId(string $section): string
    {
        do {
            $id = self::ITEM_SECTIONS[$section] . '-' . bin2hex(random_bytes(2));
        } while ($this->findItem($id) !== null);

        return $id;
    }

    /**
     * Remove the leading heading of a section body.
     *
     * @param string $body Body.
     * @param string $id Section id.
     * @return string
     */
    private static function stripHeading(string $body, string $id): string
    {
        $body = ltrim($body, "\n");
        $first = strtok($body, "\n");
        if ($first !== false && preg_match('/^##\s/', $first)) {
            return (string)substr($body, strlen($first));
        }

        return $body;
    }

    /**
     * @param string $body Body of the changelog section.
     * @return array<int, string>
     */
    private static function parseChangelog(string $body): array
    {
        $lines = [];
        foreach (explode("\n", trim($body)) as $line) {
            if (preg_match('/^[-*] (.+)$/', rtrim($line), $match)) {
                $lines[] = self::cleanLine($match[1]);
            }
        }

        return $lines;
    }

    /**
     * Body of a text section without comments that would be taken for the structure of the document.
     *
     * @param string $body Markdown.
     * @return string
     */
    private static function cleanBody(string $body): string
    {
        return trim((string)preg_replace('/<!--\s*\/?kb:.*?-->/s', '', $body));
    }

    /**
     * Text of an item: one line, nothing that could break the surrounding comments.
     *
     * @param string $text Text.
     * @return string
     */
    public static function cleanText(string $text): string
    {
        return mb_substr(self::cleanLine($text), 0, self::MAX_ITEM_TEXT);
    }

    /**
     * @param string $text Text.
     * @return string
     */
    private static function cleanLine(string $text): string
    {
        $text = str_replace(['<!--', '-->'], ['<!–', '–>'], $text);

        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @param array<int, string> $src References.
     * @return array<int, string>
     */
    public static function cleanSrc(array $src): array
    {
        $clean = [];
        foreach ($src as $reference) {
            $reference = (string)preg_replace('/[^A-Za-z0-9:_.\-]/', '', (string)$reference);
            if ($reference !== '') {
                $clean[] = $reference;
            }
        }

        return array_values(array_unique($clean));
    }

    /**
     * @param string $id Section id.
     * @return void
     */
    private function assertTextSection(string $id): void
    {
        if (!in_array($id, self::TEXT_SECTIONS, true)) {
            throw new InvalidArgumentException("Not a text section: $id");
        }
    }

    /**
     * @param string $id Section id.
     * @return void
     */
    private function assertItemSection(string $id): void
    {
        if (!isset(self::ITEM_SECTIONS[$id])) {
            throw new InvalidArgumentException("Not an item section: $id");
        }
    }
}
