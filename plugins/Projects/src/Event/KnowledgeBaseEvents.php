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
     * Never breaks the heartbeat and never prints anything.
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

        // The heartbeat runs from cron, which mails whatever a command prints. The command copies log messages to
        // the console, so that is switched off for this work; the run is written to logs/ai.log (errors also to
        // logs/error.log).
        $event->getData('io')?->setLoggers(false);

        $lock = fopen(TMP . 'projects_knowledge_base.lock', 'c');
        if ($lock === false) {
            return;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            return;
        }

        try {
            KbUpdater::fromConfig()->run([
                'projects' => (int)($config['hourlyProjects'] ?? 5),
                'maxEvents' => (int)($config['maxEvents'] ?? 60),
                'maxSeconds' => (int)($config['maxSeconds'] ?? 2400),
            ]);
        } catch (Throwable $e) {
            Log::error('Knowledge base update failed: ' . get_class($e) . ': ' . $e->getMessage(), ['scope' => 'ai']);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
