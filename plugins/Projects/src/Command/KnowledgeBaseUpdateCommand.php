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
 * Brings the knowledge base of projects up to date with new events.
 *
 * The hourly heartbeat does the same for the projects that wait longest. This command is for a single project,
 * a first run over the history of a project (raise --max-events) and for looking at what the model would do
 * (--dry-run).
 */
class KnowledgeBaseUpdateCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'knowledge_base update';
    }

    /**
     * @param \Cake\Console\ConsoleOptionParser $parser Parser.
     * @return \Cake\Console\ConsoleOptionParser
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return parent::buildOptionParser($parser)
            ->setDescription('Update the knowledge base of projects with their new events.')
            ->addOption('project', [
                'short' => 'p',
                'help' => 'Id or number of the only project to update (also when it is not active).',
            ])
            ->addOption('projects', [
                'help' => 'Projects with new events to deal with (default: Projects.KnowledgeBase.hourlyProjects).',
            ])
            ->addOption('max-events', [
                'help' => 'Events to read per project (default: Projects.KnowledgeBase.maxEvents).',
            ])
            ->addOption('max-seconds', [
                'help' => 'No new project is started after this many seconds (default: ...KnowledgeBase.maxSeconds).',
            ])
            ->addOption('dry-run', [
                'boolean' => true,
                'help' => 'Show what the model proposes, change nothing.',
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
            'projects' => (int)($args->getOption('projects') ?? $config['hourlyProjects'] ?? 5),
            'maxEvents' => (int)($args->getOption('max-events') ?? $config['maxEvents'] ?? 60),
            'maxSeconds' => (int)($args->getOption('max-seconds') ?? $config['maxSeconds'] ?? 2400),
            'dryRun' => (bool)$args->getOption('dry-run'),
        ];
        if ($args->getOption('project')) {
            $options['project'] = (string)$args->getOption('project');
            $options['projects'] = 1;
        }

        $report = KbUpdater::fromConfig()->run($options);

        foreach ($report->messages as $message) {
            $io->out($message);
        }
        $io->out(sprintf(
            '%d projects, %d events read, %d revisions written, %d events failed.',
            $report->projects,
            $report->events,
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
