<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Queue\QueueManager;
use Cake\Utility\Text;

/**
 * AI Controller
 */
class AiController extends AppController
{
    /**
     * Before filter callback to allow unauthenticated access to chat and clearHistory actions.
     *
     * @param \Cake\Event\EventInterface $event The event object.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->FormProtection->setConfig('unlockedActions', ['chat', 'clearHistory']);
    }

    /**
     * Accepts a user message, pushes an AiChatJob onto the queue, and immediately
     * returns the job ID. The client polls chatStatus() until the result is ready.
     */
    public function chat(): Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod('post');

        $userInput = trim((string)$this->request->getData('message'));
        if ($userInput === '') {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode(['error' => 'Empty message', 'job_id' => null]));
        }

        $session = $this->request->getSession();
        $history = $session->read('AIAssistant.history') ?? [];
        $userId = $this->getCurrentUser()->get('id');

        $jobId = Text::uuid();
        $jobsDir = TMP . 'ai_jobs' . DS;

        if (!is_dir($jobsDir)) {
            mkdir($jobsDir, 0755, true);
        }

        $this->cleanupOldJobs($jobsDir);

        QueueManager::push('AiChat', [
            'user_id' => $userId,
            'message' => $userInput,
            'history' => $history,
            'job_id' => $jobId,
        ]);

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['job_id' => $jobId, 'status' => 'pending']));
    }

    /**
     * Polls the result file for the given job ID and returns the AI response
     * when ready. On success the updated history is written back to the session
     * and the result file is removed.
     */
    public function chatStatus(): Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod('get');

        $jobId = (string)$this->request->getQuery('job_id');

        // Validate UUID format to prevent path traversal.
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $jobId)) {
            return $this->response
                ->withStatus(400)
                ->withType('application/json')
                ->withStringBody((string)json_encode(['error' => 'Invalid job ID']));
        }

        $resultFile = TMP . 'ai_jobs' . DS . $jobId . '_result.json';

        if (!file_exists($resultFile)) {
            return $this->response
                ->withType('application/json')
                ->withStringBody((string)json_encode(['status' => 'pending']));
        }

        $result = json_decode((string)file_get_contents($resultFile), true);

        if (!is_array($result) || ($result['user_id'] ?? null) !== $this->getCurrentUser()->get('id')) {
            return $this->response
                ->withStatus(403)
                ->withType('application/json')
                ->withStringBody((string)json_encode(['error' => 'Forbidden']));
        }

        unlink($resultFile);

        if (!empty($result['history'])) {
            $this->request->getSession()->write('AIAssistant.history', $result['history']);
        }

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode([
                'status' => $result['status'],
                'response' => $result['response'] ?? null,
                'redirect' => $result['redirect'] ?? null,
                'error' => $result['error'] ?? null,
            ]));
    }

    /**
     * Returns whether the queue worker is alive based on a heartbeat file written
     * by AiChatJob. The worker is considered running if the file exists and was
     * modified within the last 35 minutes.
     */
    public function workerStatus(): Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod('get');

        $heartbeat = TMP . 'ai_jobs' . DS . 'worker_heartbeat';
        $running = file_exists($heartbeat) && (time() - (int)filemtime($heartbeat)) < 2100;

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['running' => $running]));
    }

    /**
     * Clears the AI conversation history for the current user.
     */
    public function clearHistory(): Response
    {
        $this->Authorization->skipAuthorization();
        $this->request->allowMethod('post');

        $this->request->getSession()->delete('AIAssistant.history');

        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode(['cleared' => true]));
    }

    /**
     * Removes job result files older than one hour from the ai_jobs directory.
     *
     * @param string $jobsDir Absolute path to the jobs directory (with trailing DS).
     * @return void
     */
    private function cleanupOldJobs(string $jobsDir): void
    {
        $cutoff = time() - 3600;
        $files = glob($jobsDir . '*.json') ?: [];

        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'worker_heartbeat' && filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}
