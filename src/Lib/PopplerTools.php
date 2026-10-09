<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Core\Configure;
use RuntimeException;

/**
 * Thin wrapper around the poppler `pdftotext` command line utility.
 *
 * The executable is read from the `Poppler.pdftotext` configuration key. By default it is just
 * the program name, which is looked up in PATH.
 */
class PopplerTools
{
    /**
     * Seconds the process may run.
     */
    private const TIMEOUT = 60;

    /**
     * Returns the pdftotext executable (a program name or a full path).
     *
     * @return string
     */
    public function getExecutable(): string
    {
        return (string)Configure::read('Poppler.pdftotext', 'pdftotext') ?: 'pdftotext';
    }

    /**
     * Extracts text of all pages of a PDF file.
     *
     * @param string $filePath Path to PDF file.
     * @param bool $layout Preserve the physical layout (useful for tables).
     * @return array<int, string> UTF-8 text of each page, indexed from 1.
     * @throws \RuntimeException When pdftotext cannot be started or fails.
     */
    public function extractPages(string $filePath, bool $layout = true): array
    {
        $command = [$this->getExecutable(), '-enc', 'UTF-8'];
        if ($layout) {
            $command[] = '-layout';
        }
        // "-" as output writes the text to stdout.
        $command[] = $filePath;
        $command[] = '-';

        // pdftotext ends every page, including the last one, with a form feed character.
        $pages = explode("\f", $this->run($command));
        if (end($pages) === '') {
            array_pop($pages);
        }

        return array_combine(range(1, max(1, count($pages))), $pages ?: ['']);
    }

    /**
     * Run a command without a shell and return its stdout.
     *
     * @param list<string> $command Executable followed by its arguments.
     * @return string
     * @throws \RuntimeException When the process cannot be started, times out or exits with an error.
     */
    protected function run(array $command): string
    {
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Could not start ' . basename($command[0]) . '.');
        }

        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $stdout = '';
        $stderr = '';
        $deadline = time() + self::TIMEOUT;
        do {
            $stdout .= (string)stream_get_contents($pipes[1]);
            $stderr .= (string)stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            if (time() > $deadline) {
                proc_terminate($process);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                throw new RuntimeException(basename($command[0]) . ' timed out.');
            }
            usleep(20000);
        } while (true);

        $stdout .= (string)stream_get_contents($pipes[1]);
        $stderr .= (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        // proc_get_status() reports the real exit code only on the first call after exit.
        if ($status['exitcode'] > 0) {
            $reason = trim($stderr !== '' ? $stderr : 'exit code ' . $status['exitcode']);

            throw new RuntimeException(basename($command[0]) . ' failed: ' . $reason);
        }

        return $stdout;
    }
}
