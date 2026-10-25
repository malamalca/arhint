<?php
declare(strict_types=1);

namespace Projects\Event;

use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Projects\Lib\KnowledgeBase\KbUpdater;
use Throwable;

/**
 * Keeps the knowledge base of projects up to date from the hourly heartbeat (`bin/cake hourly`).
 */
class KnowledgeBaseEvents implements EventListenerInterface
{
    /**
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        return [
            'App.HeartBeat.hourlyRun' => 'hourlyRun',
        ];
    }

    /**
     * Update the projects that wait longest, unless the feature is off or the previous run is still going.
     *
     * Never breaks the heartbeat.
     *
     * @param \Cake\Event\Event $event Event; `io` is the console of the heartbeat.
     * @return void
     */
    public function hourlyRun(Event $event): void
    {
        $config = (array)Configure::read('Projects.KnowledgeBase');
        if (empty($config['enabled'])) {
            return;
        }

        $lock = fopen(TMP . 'projects_knowledge_base.lock', 'c');
        if ($lock === false) {
            return;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            return;
        }

        try {
            $report = KbUpdater::fromConfig()->run([
                'projects' => (int)($config['hourlyProjects'] ?? 5),
                'maxEvents' => (int)($config['maxEvents'] ?? 60),
                'maxSeconds' => (int)($config['maxSeconds'] ?? 2400),
            ]);

            $io = $event->getData('io');
            if ($io !== null && $report->projects > 0) {
                $io->out(sprintf(
                    'Knowledge base: %d projects, %d events, %d revisions, %d failed.',
                    $report->projects,
                    $report->events,
                    $report->revisions,
                    $report->failed,
                ));
            }
        } catch (Throwable $e) {
            Log::error('Knowledge base update failed: ' . get_class($e) . ': ' . $e->getMessage(), ['scope' => 'ai']);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
