<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use InvalidArgumentException;
use RuntimeException;

class LilPdfProcessor
{
    public const FORMAT_PDF = 1;
    public const FORMAT_PNG = 2;

    /**
     * @var string Path to the Ghostscript executable
     */
    protected string $gsExecutablePath;

    /**
     * @var array<string> List of files to be processed
     */
    protected array $filesList = [];

    /**
     * LilPdfProcessor constructor.
     */
    public function __construct()
    {
        // Set the path to the Ghostscript executable
        $this->gsExecutablePath = Configure::read('Ghostscript.executable', '/usr/bin/gs');

        if (!file_exists($this->gsExecutablePath)) {
            throw new RuntimeException(sprintf(__('Ghostscript not found at path: {0}', $this->gsExecutablePath)));
        }
    }

    /**
     * Get the count of files in the processing list
     *
     * @return int The number of files in the processing list
     */
    public function count(): int
    {
        return count($this->filesList);
    }

    /**
     * Add a single file to the processing list
     *
     * @param string $filePath The path to the file to be added
     * @return \App\Lib\LilPdfProcessor Returns the instance for method chaining
     * @throws \InvalidArgumentException If the file does not exist
     */
    public function addFile(string $filePath): LilPdfProcessor
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException(sprintf(__('File not found: {0}', $filePath)));
        }
        $this->filesList[] = $filePath;

        return $this;
    }

    /**
     * Add multiple files to the processing list
     *
     * @param array<string> $filePaths An array of file paths to be added
     * @return \App\Lib\LilPdfProcessor Returns the instance for method chaining
     */
    public function addFiles(array $filePaths): LilPdfProcessor
    {
        foreach ($filePaths as $path) {
            $this->addFile($path);
        }

        return $this;
    }

    /**
     * Merge the PDF files in the processing list into a single PDF file
     *
     * @param string $targetFilepath The path where the merged PDF file will be saved
     * @param string $compression The compression level to be applied to the merged PDF (default: 'default')
     * @param bool $pdfa Whether to create a PDF/A compliant file (default: false)
     * @return string The path to the merged PDF file
     * @throws \RuntimeException If there are no files to merge or if the merging process fails
     * @throws \InvalidArgumentException If an invalid compression level is provided
     */
    public function mergeFiles(string $targetFilepath, string $compression = 'default', bool $pdfa = false): string
    {
        if (empty($this->filesList)) {
            throw new RuntimeException(__('No files to merge'));
        }
        if (!in_array($compression, ['default', 'screen', 'ebook', 'printer', 'prepress'], true)) {
            throw new InvalidArgumentException(__('Invalid compression level: {0}', $compression));
        }

        $outputFile = TMP . basename($targetFilepath);
        $gsParams = '-sDEVICE=pdfwrite -dBATCH -dNOPAUSE -dPDFSETTINGS=/' . $compression . ' -sOutputFile=%s %s';

        if ($pdfa) {
            $gsParams = '-dPDFA -dNOPAUSE -sDEVICE=pdfwrite -dPDFSETTINGS=/' . $compression . ' ' .
                '-sColorConversionStrategy=UseDeviceIndependentColor -dPDFACompatibilityPolicy=2 ' .
                '-sOutputFile=%s %s';
        }

        $command = sprintf(
            escapeshellarg($this->gsExecutablePath) . ' ' . $gsParams,
            escapeshellarg($outputFile),
            implode(' ', array_map('escapeshellarg', $this->filesList)),
        );

        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            throw new RuntimeException(__('Failed to merge PDF files'));
        }

        return $outputFile;
    }

    /**
     * Extract pages from the PDF files in the processing list.
     *
     * When $multiPage is false, all pages from $firstPage to $lastPage are written
     * into a single output file. When $multiPage is true, each page is written to a
     * separate numbered file (e.g. basename_001.pdf).
     *
     * @param int $firstPage The first page to extract (1-based index)
     * @param int $lastPage The last page to extract; defaults to $firstPage when -1
     * @param bool $multiPage When true, write one file per page using a numbered pattern
     * @param string|null $outputBasename Base name (without extension) for output files;
     *   when null the source file's basename is used
     * @param int $format The output format (FORMAT_PDF or FORMAT_PNG)
     * @return array<string>|false Returns an array of output file paths on success, or false on failure
     */
    public function extractPages(
        int $firstPage,
        int $lastPage = -1,
        bool $multiPage = false,
        ?string $outputBasename = null,
        int $format = self::FORMAT_PDF,
    ): array|false {
        if ($lastPage < $firstPage) {
            $lastPage = $firstPage;
        }

        if ($format === self::FORMAT_PNG) {
            $deviceParam = '-sDEVICE=png16m -r600';
            $outputExtension = '.png';
        } else {
            $deviceParam = '-sDEVICE=pdfwrite';
            $outputExtension = null; // resolved per-file below
        }

        $gsParams = $deviceParam . ' -dBATCH -dNOPAUSE -dFirstPage=%3$s -dLastPage=%4$s -sOutputFile=%2$s %1$s';

        $ret = [];

        foreach ($this->filesList as $file) {
            $ext = $outputExtension ?? substr(basename($file), -4);
            $baseName = $outputBasename ?? substr(basename($file), 0, -4);

            if ($multiPage) {
                // Run GS once per page to avoid passing a %03d pattern through
                // cmd.exe on Windows, where escapeshellarg expands % sequences.
                for ($page = $firstPage; $page <= $lastPage; $page++) {
                    $pageFile = TMP . $baseName . '_' . sprintf('%03d', $page) . $ext;

                    $command = sprintf(
                        escapeshellarg($this->gsExecutablePath) . ' ' . $gsParams,
                        escapeshellarg($file),
                        escapeshellarg($pageFile),
                        $page,
                        $page,
                    );

                    exec($command, $output, $returnVar);

                    if ($returnVar !== 0) {
                        return false;
                    }

                    if (file_exists($pageFile)) {
                        $ret[] = $pageFile;
                    }
                }
            } else {
                $outputFile = TMP . $baseName . $ext;

                $command = sprintf(
                    escapeshellarg($this->gsExecutablePath) . ' ' . $gsParams,
                    escapeshellarg($file),
                    escapeshellarg($outputFile),
                    $firstPage,
                    $lastPage,
                );

                exec($command, $output, $returnVar);

                if ($returnVar !== 0) {
                    return false;
                }

                if (!file_exists($outputFile)) {
                    return false;
                }
                $ret[] = $outputFile;
            }
        }

        return $ret ?: false;
    }
}
