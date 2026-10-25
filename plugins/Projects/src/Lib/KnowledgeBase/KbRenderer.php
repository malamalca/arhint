<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Renders the knowledge base to safe HTML.
 */
class KbRenderer
{
    /**
     * HTML of the knowledge base for reading. Raw HTML in the text is dropped.
     *
     * With `$linked`, every open deadline that has a date gets a link for the personal calendar:
     * `<a class="kb-calendar" data-item="ID" data-linked="1|0">`, empty, for the page to fill and handle.
     *
     * @param \Projects\Lib\KnowledgeBase\KbDocument $doc Document.
     * @param array<string, true>|null $linked Ids of the deadlines in the calendar of the user; null when the user
     *   has no calendar and no links are wanted.
     * @return string
     */
    public function render(KbDocument $doc, ?array $linked = null): string
    {
        $blocks = $doc->displayBlocks();
        if ($linked === null || !isset($blocks['deadlines'])) {
            return $this->markdown($doc->toDisplayMarkdown());
        }

        $html = '';
        $pending = [];
        foreach ($blocks as $id => $markdown) {
            if ($id !== 'deadlines') {
                $pending[] = $markdown;
                continue;
            }
            $html .= $pending === [] ? '' : $this->markdown(implode("\n\n", $pending));
            $pending = [];
            $html .= $this->withCalendarLinks($this->markdown($markdown), $doc->items('deadlines'), $linked);
        }

        return $html . ($pending === [] ? '' : $this->markdown(implode("\n\n", $pending)));
    }

    /**
     * Add the calendar link to the list item of every deadline that can be in a calendar.
     *
     * The list items are in the order of the deadlines.
     *
     * @param string $html HTML of the deadlines section.
     * @param array<int, \Projects\Lib\KnowledgeBase\KbItem> $items Deadlines, in the order of the document.
     * @param array<string, true> $linked Deadlines in the calendar of the user.
     * @return string
     */
    private function withCalendarLinks(string $html, array $items, array $linked): string
    {
        $position = 0;

        $link = function (array $match) use (&$position, $items, $linked): string {
            $item = $items[$position++] ?? null;
            if ($item === null || !$item->isOpen() || $item->date === null) {
                return $match[0];
            }

            return sprintf(
                ' <a href="#" class="kb-calendar" data-item="%s" data-linked="%d"></a></li>',
                htmlspecialchars($item->id, ENT_QUOTES),
                isset($linked[$item->id]) ? 1 : 0,
            );
        };

        return (string)preg_replace_callback('/<\/li>/', $link, $html);
    }

    /**
     * HTML of a free-form description.
     *
     * @param string $markdown Markdown.
     * @return string
     */
    public function markdown(string $markdown): string
    {
        $converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            // Headings get ids so that the table of contents can link to them.
            'heading_permalink' => [
                'insert' => 'none',
                'apply_id_to_heading' => true,
                'id_prefix' => rtrim(KbDocument::ANCHOR_PREFIX, '-'),
                'fragment_prefix' => rtrim(KbDocument::ANCHOR_PREFIX, '-'),
            ],
        ]);
        $converter->getEnvironment()->addExtension(new HeadingPermalinkExtension());

        return (string)$converter->convert($markdown);
    }
}
