<?php
declare(strict_types=1);

namespace Projects\Job;

use App\Job\AiChatJob;
use App\Lib\AIAssistant;
use App\Model\Entity\User;
use Authorization\AuthorizationService;
use Authorization\Policy\OrmResolver;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Queue\Job\JobInterface;
use Cake\Queue\Job\Message;
use Interop\Queue\Processor;
use Projects\Event\KnowledgeBaseAIToolsEvents;
use Projects\Lib\KnowledgeBase\KbCalendar;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbPrompt;
use Projects\Lib\KnowledgeBase\KbRenderer;
use Projects\Lib\KnowledgeBase\KbService;
use Projects\Lib\KnowledgeBase\KbSettings;
use RuntimeException;
use Throwable;

/**
 * Answers one message of the conversation about the knowledge base of a project.
 *
 * Like AiChatJob it writes the result to a file in tmp/ai_jobs/ for the polling controller. The result also tells
 * what the conversation changed in the knowledge base and how to undo it.
 */
class KnowledgeBaseChatJob implements JobInterface
{
    /**
     * @param \Cake\Queue\Job\Message $message Queue message with user_id, project_id, message, history and job_id.
     * @return string|null Processor::ACK on success, Processor::REJECT on permanent failure.
     */
    public function execute(Message $message): ?string
    {
        $userId = (string)$message->getArgument('user_id', '');
        $projectId = (string)$message->getArgument('project_id', '');
        $userMessage = (string)$message->getArgument('message', '');
        $history = (array)$message->getArgument('history', []);
        $jobId = (string)$message->getArgument('job_id', '');

        if ($userId === '' || $projectId === '' || $userMessage === '' || $jobId === '') {
            Log::warning('KnowledgeBaseChatJob rejected: empty required argument', ['scope' => ['ai']]);

            return Processor::REJECT;
        }

        // The polling controller and the page check this file to know that the worker is alive.
        $jobsDir = TMP . 'ai_jobs' . DS;
        if (!is_dir($jobsDir)) {
            mkdir($jobsDir, 0755, true);
        }
        touch($jobsDir . 'worker_heartbeat');
        $resultFile = $jobsDir . $jobId . '_result.json';

        try {
            /** @var \App\Model\Entity\User $user */
            $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
            $user->setAuthorization(new AuthorizationService(new OrmResolver()));

            /** @var \Projects\Model\Entity\Project $project */
            $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectId);
            if (!$user->can('knowledgeBase', $project)) {
                throw new RuntimeException('No access to the project.');
            }
            if (!KbSettings::companyAllowed((string)$project->owner_id)) {
                throw new RuntimeException('The AI conversation is not on for the company of the project.');
            }

            $service = new KbService();
            $versionBefore = (int)$project->kb_version;

            $assistant = $this->createAssistant($user, $projectId);
            $assistant->setPromptAddendum((new KbPrompt())->chat(
                $service->document($project),
                trim($project->no . ' ' . $project->title),
            ));
            $assistant->setHistory($history);

            $response = $assistant->getResponse($userMessage);

            $result = [
                'user_id' => $userId,
                'project_id' => $projectId,
                'status' => 'done',
                'response' => AiChatJob::renderMarkdown($response),
                'history' => $assistant->getHistory(),
            ] + $this->changes($projectId, $user, $versionBefore);

            file_put_contents($resultFile, (string)json_encode($result));
        } catch (Throwable $e) {
            Log::error('KnowledgeBaseChatJob error: ' . get_class($e) . ': ' . $e->getMessage(), [
                'scope' => ['ai'],
                'user_id' => $userId,
                'project_id' => $projectId,
                'trace' => $e->getTraceAsString(),
            ]);

            file_put_contents($resultFile, (string)json_encode([
                'user_id' => $userId,
                'project_id' => $projectId,
                'status' => 'error',
                'error' => $e->getMessage(),
                'history' => $history,
            ]));

            return Processor::REJECT;
        }

        return Processor::ACK;
    }

    /**
     * The assistant of the conversation: bound to the project and limited to its tools.
     *
     * @param \App\Model\Entity\User $user User of the conversation.
     * @param string $projectId Project of the conversation.
     * @return \App\Lib\AIAssistant
     */
    protected function createAssistant(User $user, string $projectId): AIAssistant
    {
        $assistant = new AIAssistant($user, [KnowledgeBaseAIToolsEvents::CONTEXT_PROJECT => $projectId]);
        $assistant->restrictTools(KnowledgeBaseAIToolsEvents::CHAT_TOOLS);

        return $assistant;
    }

    /**
     * What the conversation changed in the knowledge base since the given version.
     *
     * @param string $projectId Project id.
     * @param \App\Model\Entity\User $user User of the conversation.
     * @param int $versionBefore Version of the knowledge base when the message arrived.
     * @return array<string, mixed> `changes` (summaries), `undo_revision_id`, `kb_html` and `kb_version`; empty when
     *   nothing changed.
     */
    private function changes(string $projectId, User $user, int $versionBefore): array
    {
        $revisions = TableRegistry::getTableLocator()->get('Projects.ProjectsKbRevisions');
        $made = $revisions->find()
            ->select(['id', 'version', 'summary'])
            ->where([
                'project_id' => $projectId,
                'user_id' => $user->id,
                'source' => 'chat',
                'version >' => $versionBefore,
            ])
            ->orderBy(['version' => 'ASC'])
            ->all()
            ->toList();
        if ($made === []) {
            return [];
        }

        // The state before the first change of the conversation is the one the undo returns to.
        $before = $revisions->find()
            ->select(['id'])
            ->where(['project_id' => $projectId, 'version' => $made[0]->version - 1])
            ->first();

        /** @var \Projects\Model\Entity\Project $project */
        $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectId);

        return [
            'changes' => array_map(fn($revision): string => (string)$revision->summary, $made),
            'undo_revision_id' => $before?->id,
            'kb_html' => (new KbRenderer())->render(
                KbDocument::parse((string)$project->descript),
                (new KbCalendar())->linksFor($project, $user),
            ),
            'kb_version' => (int)$project->kb_version,
        ];
    }
}
