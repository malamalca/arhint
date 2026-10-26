<?php
declare(strict_types=1);

namespace Projects\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Projects\Lib\KnowledgeBase\KbUpdater;

/**
 * Writes the status of active projects (the "Stanje" section of the knowledge base) from their current state.
 *
 * The regular update keeps the status current as new events arrive. This command is for filling it in for
 * projects whose knowledge base was made before the status existed, and for making all of them again (--all).
 * It reads no events; the status is made from the knowledge base alone.
 */
class KnowledgeBaseStatusCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'knowledge_base status';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription('Write the status of active projects from their knowledge base.')
            ->addOption('project', [
                'short' => 'p',
                'help' => 'Id or number of the only project (also when it is not active).',
            ])
            ->addOption('all', [
                'boolean' => true,
                'help' => 'Make the status again for projects that have one; by default they are left alone.',
            ])
            ->addOption('max-seconds', [
                'help' => 'No new project is started after this many seconds (default: ...KnowledgeBase.maxSeconds).',
            ])
            ->addOption('dry-run', [
                'boolean' => true,
                'help' => 'Show the statuses the model proposes, change nothing.',
            ]);
    }

    /**
     * @param \Cake\Console\Arguments $args Arguments.
     * @param \Cake\Console\ConsoleIo $io Console.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $config = (array)Configure::read('Projects.KnowledgeBase');
        $options = [
            'all' => (bool)$args->getOption('all'),
            'maxSeconds' => (int)($args->getOption('max-seconds') ?? $config['maxSeconds'] ?? 2400),
            'dryRun' => (bool)$args->getOption('dry-run'),
        ];
        if ($args->getOption('project')) {
            $options['project'] = (string)$args->getOption('project');
        }

        $report = KbUpdater::fromConfig()->statuses($options);

        foreach ($report->messages as $message) {
            $io->out($message);
        }
        $io->out(sprintf(
            '%d statuses made, %d written, %d without a usable answer.',
            $report->projects,
            $report->revisions,
            $report->failed,
        ));
        if ($report->stopped !== null) {
            $io->err('Stopped: ' . $report->stopped);

            return static::CODE_ERROR;
        }

        return static::CODE_SUCCESS;
    }
}
