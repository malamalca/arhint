<?php
declare(strict_types=1);

namespace App\Job;

use App\Lib\AIAssistant;
use Authorization\AuthorizationService;
use Authorization\Policy\OrmResolver;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Throwable;

class AiChatJob implements JobInterface
{
    /**
     * Processes the AI chat request from the queue.
     *
     * Reads user_id, message, history, and job_id from the message body,
     * calls AIAssistant::getResponse(), and writes the result to a file in
     * tmp/ai_jobs/ for the polling controller action to pick up.
     *
     * @param \Cake\Queue\Job\Message $message Queue message.
     * @return string|null Processor::ACK on success, Processor::REJECT on permanent failure.
     */
    public function execute(Message $message): ?string
    {
        $userId = (string)$message->getArgument('user_id', '');
        $userMessage = (string)$message->getArgument('message', '');
        $history = (array)$message->getArgument('history', []);
        $jobId = (string)$message->getArgument('job_id', '');

        Log::debug(
            'AiChatJob starting',
            [
                'scope' => ['ai'],
                'user_id' => $userId,
                'message' => mb_substr($userMessage, 0, 100),
                'job_id' => $jobId,
                'history_count' => count($history),
            ],
        );

        if ($userId === '' || $userMessage === '' || $jobId === '') {
            Log::warning(
                'AiChatJob rejected: empty required argument',
                ['scope' => ['ai'], 'user_id' => $userId, 'message_empty' => $userMessage === '', 'job_id' => $jobId],
            );

            return Processor::REJECT;
        }

        // Touch a heartbeat file so the web app can check the worker is alive.
        $jobsDir = TMP . 'ai_jobs' . DS;
        if (!is_dir($jobsDir)) {
            mkdir($jobsDir, 0755, true);
        }
        touch($jobsDir . 'worker_heartbeat');

        $resultFile = TMP . 'ai_jobs' . DS . $jobId . '_result.json';

        try {
            /** @var \App\Model\Entity\User $user */
            $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        } catch (Throwable $e) {
            file_put_contents($resultFile, (string)json_encode([
                'user_id' => $userId,
                'status' => 'error',
                'error' => 'Could not load user: ' . $e->getMessage(),
                'history' => $history,
            ]));

            return Processor::REJECT;
        }

        try {
            $user->setAuthorization(new AuthorizationService(new OrmResolver()));

            $assistant = new AIAssistant($user);
            $assistant->setHistory($history);

            $response = $assistant->getResponse($userMessage);

            $converter = new GithubFlavoredMarkdownConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]);
            $responseHtml = (string)$converter->convert($response);

            Log::debug(
                'AiChatJob writing result',
                [
                    'scope' => ['ai'],
                    'job_id' => $jobId,
                    'result_file' => $resultFile,
                    'response_len' => strlen($responseHtml),
                ],
            );

            file_put_contents($resultFile, (string)json_encode([
                'user_id' => $userId,
                'status' => 'done',
                'response' => $responseHtml,
                'redirect' => $assistant->getRedirectUrl(),
                'history' => $assistant->getHistory(),
            ]));
        } catch (Throwable $e) {
            Log::error(
                'AI ChatJob error: ' . get_class($e) . ': ' . $e->getMessage() . ' | File: ' . $e->getFile() .
                ':' . $e->getLine(),
                [
                    'scope' => ['ai'],
                    'user_id' => $userId,
                    'job_id' => $jobId,
                    'trace' => $e->getTraceAsString(),
                    'previous' => $e->getPrevious() ? $e->getPrevious()->getMessage() : null,
                ],
            );

            file_put_contents($resultFile, (string)json_encode([
                'user_id' => $userId,
                'status' => 'error',
                'error' => $e->getMessage(),
                'history' => $history,
            ]));

            return Processor::REJECT;
        }

        return Processor::ACK;
    }
}
