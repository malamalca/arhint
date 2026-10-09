<?php
declare(strict_types=1);

namespace App\Lib;

use App\Model\Entity\Attachment;
use Cake\Core\Configure;
use Exception;
use mishahawthorn\OCRmyPDF\OCRmyPDF;
use RuntimeException;

/**
 * Extracts plain text from stored attachments.
 *
 * PDF files are read with poppler (pdftotext), optionally after OCR. Common text formats
 * are read directly. Used by the AI assistant tools and by background AI analysis jobs.
 */
class AttachmentTextReader
{
    /**
     * Largest plain text attachment that will be read, in bytes.
     */
    private const MAX_TEXT_BYTES = 2097152;

    /**
     * Extensions read as plain text.
     */
    private const TEXT_EXTENSIONS = ['txt', 'csv', 'md', 'json', 'xml', 'html', 'htm'];

    /**
     * Returns real path of the attachment file, or null when it is missing or outside its upload folder.
     *
     * The file must sit directly in the upload folder of its model because filenames come from users.
     *
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @return string|null
     */
    public function resolvePath(Attachment $attachment): ?string
    {
        $uploadRoot = realpath((string)Configure::read('App.uploadFolder'));
        $modelFolder = $uploadRoot === false ? false : realpath($uploadRoot . DS . $attachment->model);
        $realPath = realpath($attachment->getFilePath());
        if (
            $modelFolder === false
            || $realPath === false
            || !is_file($realPath)
            || !str_starts_with($modelFolder, $uploadRoot . DIRECTORY_SEPARATOR)
            || dirname($realPath) !== $modelFolder
        ) {
            return null;
        }

        return $realPath;
    }

    /**
     * Whether text can be extracted from the attachment type.
     *
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @return bool
     */
    public function isReadable(Attachment $attachment): bool
    {
        return $this->isPdf($attachment) || $this->isText($attachment);
    }

    /**
     * Read the text of an attachment.
     *
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @param int $firstPage First PDF page (1-based).
     * @param int|null $lastPage Last PDF page, null for the last page of the document.
     * @param bool $ocr Run OCR first (PDF only), for documents without a text layer.
     * @return array{text?: string, pages?: int, first_page?: int, last_page?: int, error?: string}
     */
    public function read(Attachment $attachment, int $firstPage = 1, ?int $lastPage = null, bool $ocr = false): array
    {
        $path = $this->resolvePath($attachment);
        if ($path === null) {
            return ['error' => 'Attachment file is not available on the server.'];
        }

        if ($this->isPdf($attachment)) {
            return $this->readPdf($path, $firstPage, $lastPage, $ocr);
        }

        if ($this->isText($attachment)) {
            if (filesize($path) > self::MAX_TEXT_BYTES) {
                return ['error' => 'Text attachment is too large to read.'];
            }
            $text = (string)file_get_contents($path);
            if (!mb_check_encoding($text, 'UTF-8')) {
                $text = mb_convert_encoding($text, 'UTF-8', 'Windows-1250');
            }

            return ['text' => $text];
        }

        return ['error' => 'Unsupported attachment type; only PDF and text files can be read.'];
    }

    /**
     * Read a PDF; when it has no text layer retry with OCR. Intended for background jobs.
     *
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @return array{text?: string, pages?: int, first_page?: int, last_page?: int, error?: string}
     */
    public function readWithOcrFallback(Attachment $attachment): array
    {
        $result = $this->read($attachment);
        if (isset($result['error']) && $this->isPdf($attachment) && !empty($result['no_text_layer'])) {
            return $this->read($attachment, ocr: true);
        }

        return $result;
    }

    /**
     * Extract text from a PDF.
     *
     * @param string $path Absolute path to the PDF file.
     * @param int $firstPage First page (1-based).
     * @param int|null $lastPage Last page.
     * @param bool $ocr Run OCR first.
     * @return array{text?: string, pages?: int, first_page?: int, last_page?: int, error?: string, no_text_layer?: bool}
     */
    private function readPdf(string $path, int $firstPage, ?int $lastPage, bool $ocr): array
    {
        $poppler = new PopplerTools();
        $ocrTmpFile = null;

        try {
            if ($ocr) {
                $ocrTmpFile = $this->runOcr($path);
                $path = $ocrTmpFile;
            }

            $allPages = $poppler->extractPages($path);
            $pages = count($allPages);
            $first = max(1, $firstPage);
            $last = $lastPage !== null && $lastPage > 0 ? min($pages, $lastPage) : $pages;
            if ($first > $last) {
                return ['error' => 'first_page is beyond the last page. The document has ' . $pages . ' pages.'];
            }

            $text = implode("\n\n", array_slice($allPages, $first - 1, $last - $first + 1));
        } catch (Exception $e) {
            return ['error' => 'Could not read PDF: ' . $e->getMessage()];
        } finally {
            if ($ocrTmpFile !== null && is_file($ocrTmpFile)) {
                unlink($ocrTmpFile);
            }
        }

        $info = ['pages' => $pages, 'first_page' => $first, 'last_page' => $last];

        $text = trim($text);
        if ($text === '') {
            return $info + [
                'error' => $ocr
                    ? 'No text could be recognised in the PDF.'
                    : 'The PDF has no text layer (probably a scan). Call again with ocr=true.',
                'no_text_layer' => !$ocr,
            ];
        }

        return $info + ['text' => $text];
    }

    /**
     * Run OCRmyPDF and return the path of the temporary result.
     *
     * The original attachment is never modified.
     *
     * @param string $path Absolute path to the source PDF.
     * @return string Path to the temporary OCR-ed PDF; the caller must delete it.
     * @throws \RuntimeException When OCR fails.
     */
    private function runOcr(string $path): string
    {
        $gs = (string)Configure::read('Ghostscript.executable');
        if ($gs !== '') {
            putenv('PATH=' . getenv('PATH') . PATH_SEPARATOR . dirname($gs));
        }

        $ocr = OCRmyPDF::make($path);
        $ocr->setExecutable(Configure::read('OCRMyPDF.executable'));
        foreach ((array)Configure::read('OCRMyPDF.options', []) as $option) {
            $parts = explode(' ', $option, 2);
            $ocr->setParam($parts[0], $parts[1] ?? null);
        }

        $result = $ocr->run();
        if (!is_file($result) || !filesize($result)) {
            throw new RuntimeException('OCR processing failed.');
        }

        return $result;
    }

    /**
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @return bool
     */
    private function isPdf(Attachment $attachment): bool
    {
        return $this->extension($attachment) === 'pdf' || (string)$attachment->mimetype === 'application/pdf';
    }

    /**
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @return bool
     */
    private function isText(Attachment $attachment): bool
    {
        return str_starts_with((string)$attachment->mimetype, 'text/')
            || in_array($this->extension($attachment), self::TEXT_EXTENSIONS, true);
    }

    /**
     * @param \App\Model\Entity\Attachment $attachment Attachment.
     * @return string
     */
    private function extension(Attachment $attachment): string
    {
        return strtolower((string)($attachment->ext ?: pathinfo((string)$attachment->filename, PATHINFO_EXTENSION)));
    }
}
