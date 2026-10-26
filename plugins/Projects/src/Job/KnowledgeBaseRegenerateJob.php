<?php
declare(strict_types=1);

namespace Projects\Job;

use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;
use Projects\Lib\KnowledgeBase\KbUpdater;
use Projects\Lib\KnowledgeBase\KbUpdateReport;
use Projects\Model\Entity\Project;
use Throwable;

/**
 * Makes the knowledge base of a project again from all of its events, see KbUpdater::regenerate().
 *
 * It can take long, one request to the model per part of the history, so it is a job. It shares the lock of the
 * hourly update, so the two never work at the same time; when the update is running the job waits in the queue.
 */
class KnowledgeBaseRegenerateJob implements JobInterface
{
    /**
     * File that tells that a regeneration of the project is queued or running.
     *
     * @param string $projectId Project id.
     * @return string
     */
    public static function markerFile(string $projectId): string
    {
        return TMP . 'projects_kb_regenerate_' . preg_replace('/[^0-9a-f-]/i', '', $projectId);
    }

    /**
     * Whether a regeneration of the project is queued or running.
     *
     * @param string $projectId Project id.
     * @return bool
     */
    public static function isPending(string $projectId): bool
    {
        $file = self::markerFile($projectId);

        // A marker that is older than the longest run is a leftover of a job that never finished.
        return file_exists($file) && filemtime($file) > time() - KbUpdater::REGENERATE_SECONDS - 3600;
    }

    /**
     * @param \Cake\Queue\Job\Message $message Queue message with project_id and user_id.
     * @return string|null Processor::ACK when done, REQUEUE while the hourly update is running, REJECT on failure.
     */
    public function execute(Message $message): ?string
    {
        $projectId = (string)$message->getArgument('project_id', '');
        $userId = (string)$message->getArgument('user_id', '');
        if ($projectId === '') {
            return Processor::REJECT;
        }

        $lock = fopen(TMP . 'projects_knowledge_base.lock', 'c');
        if ($lock === false) {
            return Processor::REJECT;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);

            return Processor::REQUEUE;
        }

        $report = new KbUpdateReport();
        try {
            $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectId);
            if (!$project instanceof Project) {
                return Processor::REJECT;
            }

            KbUpdater::fromConfig()->regenerate(
                $project,
                $report,
                false,
                $userId === '' ? null : $userId,
                time() + KbUpdater::REGENERATE_SECONDS,
            );
            Log::info(
                'Knowledge base regeneration of ' . $project->no . ': ' . implode(' | ', $report->messages)
                    . ($report->stopped !== null ? ' | stopped: ' . $report->stopped : ''),
                ['scope' => 'ai'],
            );
        } catch (Throwable $e) {
            Log::error('Knowledge base regeneration failed: ' . get_class($e) . ': ' . $e->getMessage(), [
                'scope' => 'ai',
                'project_id' => $projectId,
            ]);

            return Processor::REJECT;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            if (file_exists(self::markerFile($projectId))) {
                unlink(self::markerFile($projectId));
            }
        }

        return Processor::ACK;
    }
}
