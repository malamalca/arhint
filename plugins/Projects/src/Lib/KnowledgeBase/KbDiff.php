<?php
declare(strict_types=1);

namespace Projects\Lib\KnowledgeBase;

/**
 * Line diff between two states of the knowledge base, for the history page.
 */
class KbDiff
{
    /** Above this many line pairs the diff is not computed and the whole new text is shown as added. */
    private const MAX_CELLS = 4000000;

    /**
     * Differences between two documents, as the user reads them.
     *
     * Structure comments, the table of contents and empty lines are left out; item attributes
     * (the comment that ends an item line) are therefore not compared.
     *
     * @param string|null $old Markdown of the older state, null when there is none.
     * @param string $new Markdown of the newer state.
     * @return array<int, array{0: string, 1: string}> Pairs of type (`same`, `added`, `removed`) and line.
     */
    public function diff(?string $old, string $new): array
    {
        return $this->lines($this->visibleLines($old ?? ''), $this->visibleLines($new));
    }

    /**
     * @param string $markdown Markdown.
     * @return array<int, string>
     */
    private function visibleLines(string $markdown): array
    {
        $markdown = str_replace("\r\n", "\n", $markdown);
        $markdown = (string)preg_replace('/<!-- kb:section id=toc -->.*?<!-- \/kb:section -->/s', '', $markdown);
        $markdown = (string)preg_replace('/\s*<!-- \/?kb:.*?-->/s', '', $markdown);

        $lines = [];
        foreach (explode("\n", $markdown) as $line) {
            $line = rtrim($line);
            if ($line !== '') {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param array<int, string> $old Old lines.
     * @param array<int, string> $new New lines.
     * @return array<int, array{0: string, 1: string}>
     */
    private function lines(array $old, array $new): array
    {
        $n = count($old);
        $m = count($new);
        if ($n * $m > self::MAX_CELLS) {
            return array_map(fn(string $line): array => ['added', $line], $new);
        }

        // Longest common subsequence, filled from the end so that the walk below goes forward.
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $old[$i] === $new[$j]
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $result = [];
        $i = 0;
        $j = 0;
        while ($i < $n && $j < $m) {
            if ($old[$i] === $new[$j]) {
                $result[] = ['same', $new[$j]];
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $result[] = ['removed', $old[$i++]];
            } else {
                $result[] = ['added', $new[$j++]];
            }
        }
        for (; $i < $n; $i++) {
            $result[] = ['removed', $old[$i]];
        }
        for (; $j < $m; $j++) {
            $result[] = ['added', $new[$j]];
        }

        return $result;
    }
}
