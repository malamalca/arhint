<?php
declare(strict_types=1);

namespace App\Test\TestCase;

/**
 * Builds minimal PDF files for tests.
 */
trait MakesPdfTrait
{
    /**
     * Write a minimal PDF with one line of text per page.
     *
     * @param string $path Destination file path.
     * @param array<string> $pageTexts Text of each page; an empty string makes a page without text.
     * @return void
     */
    protected function writePdf(string $path, array $pageTexts): void
    {
        $objects = [];
        $kids = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $next = 4;
        foreach ($pageTexts as $text) {
            $stream = $text === '' ? '' : 'BT /F1 14 Tf 72 700 Td (' . $text . ') Tj ET';
            $pageNo = $next++;
            $contentNo = $next++;
            $kids[] = $pageNo . ' 0 R';
            $objects[$pageNo] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                . '/Contents ' . $contentNo . ' 0 R /Resources << /Font << /F1 3 0 R >> >> >>';
            $objects[$contentNo] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

        file_put_contents($path, $pdf);
    }
}
