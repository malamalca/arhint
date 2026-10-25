<?php
declare(strict_types=1);

namespace Projects\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\Queue\QueueManager;
use Cake\Utility\Text;
use InvalidArgumentException;
use Projects\Lib\KnowledgeBase\KbCalendar;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbRenderer;
use Projects\Lib\KnowledgeBase\KbService;
use Projects\Model\Entity\Project;

/**
 * Conversation with the AI about the knowledge base of a project.
 *
 * Works like the assistant on the dashboard: a message is queued, the page polls for the answer. The conversation
 * is kept in the session, per project, and is gone with it; what stays are the changes of the knowledge base and its
 * changelog.
 */
class ProjectsKnowledgeController extends AppController
{
    private const JOB_MAX_AGE = 3600;

    /**
     * @param \Cake\Event\EventInterface $event Event.
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // The page sends these with the CSRF token of the page in a header.
        $this->FormProtection->setConfig('unlockedActions', ['chat', 'clearChat', 'undo', 'calendar']);
    }

    /**
     * Queue a message of the user.
     *
     * @param string|null $projectId Project id.
     * @return \Cake\Http\Response
     */
    public function chat(?string $projectId = null): Response
    {
        $this->request->allowMethod('post');
        $project = $this->loadProject($projectId);

        $message = trim((string)$this->request->getData('message'));
        if ($message === '') {
            return $this->json(['error' => __d('projects', 'Empty message.'), 'job_id' => null]);
        }

        $aiConfig = $this->getCurrentUser()->getProperty('ai_assistant');
        if (!$aiConfig || empty($aiConfig->provider) || $aiConfig->provider === 'none') {
            return $this->json(['error' => __d('projects', 'The AI assistant is not configured for your account.')]);
        }

        $session = $this->request->getSession();
        $pendingKey = $this->sessionKey($project, 'pendingJobId');
        $pending = (string)$session->read($pendingKey);
        if ($pending !== '' && !file_exists($this->resultFile($pending))) {
            // One message at a time; the page keeps polling the one that is running.
            return $this->json(['job_id' => $pending, 'status' => 'pending']);
        }

        $jobId = Text::uuid();
        $session->write($pendingKey, $jobId);
        $this->removeOldResults();

        QueueManager::push('Projects.KnowledgeBaseChat', [
            'user_id' => (string)$this->getCurrentUser()->get('id'),
            'project_id' => (string)$project->id,
            'message' => $message,
            'history' => $session->read($this->sessionKey($project, 'history')) ?? [],
            'job_id' => $jobId,
        ]);

        return $this->json(['job_id' => $jobId, 'status' => 'pending']);
    }

    /**
     * Answer of a queued message, when it is ready.
     *
     * @param string|null $projectId Project id.
     * @return \Cake\Http\Response
     */
    public function chatStatus(?string $projectId = null): Response
    {
        $this->request->allowMethod('get');
        $project = $this->loadProject($projectId);

        $jobId = (string)$this->request->getQuery('job_id');
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $jobId)) {
            return $this->json(['error' => 'Invalid job ID'])->withStatus(400);
        }

        $file = $this->resultFile($jobId);
        if (!file_exists($file)) {
            return $this->json(['status' => 'pending']);
        }

        $result = json_decode((string)file_get_contents($file), true);
        if (
            !is_array($result)
            || ($result['user_id'] ?? null) !== $this->getCurrentUser()->get('id')
            || ($result['project_id'] ?? null) !== $project->id
        ) {
            return $this->json(['error' => 'Forbidden'])->withStatus(403);
        }

        unlink($file);
        $session = $this->request->getSession();
        $session->delete($this->sessionKey($project, 'pendingJobId'));
        if (!empty($result['history'])) {
            $session->write($this->sessionKey($project, 'history'), $result['history']);
        }

        return $this->json([
            'status' => $result['status'] ?? 'error',
            'response' => $result['response'] ?? null,
            'error' => $result['error'] ?? null,
            'changes' => $result['changes'] ?? [],
            'undo_revision_id' => $result['undo_revision_id'] ?? null,
            'kb_html' => $result['kb_html'] ?? null,
            'kb_version' => $result['kb_version'] ?? null,
        ]);
    }

    /**
     * Forget the conversation.
     *
     * @param string|null $projectId Project id.
     * @return \Cake\Http\Response
     */
    public function clearChat(?string $projectId = null): Response
    {
        $this->request->allowMethod('post');
        $project = $this->loadProject($projectId);

        $session = $this->request->getSession();
        $session->delete($this->sessionKey($project, 'history'));
        $session->delete($this->sessionKey($project, 'pendingJobId'));

        return $this->json(['cleared' => true]);
    }

    /**
     * Return the knowledge base to the state a change of the conversation started from.
     *
     * @param string|null $projectId Project id.
     * @return \Cake\Http\Response
     */
    public function undo(?string $projectId = null): Response
    {
        $this->request->allowMethod('post');
        $project = $this->loadProject($projectId);

        try {
            (new KbService())->revert(
                (string)$project->id,
                (string)$this->request->getData('revision_id'),
                (string)$this->getCurrentUser()->get('id'),
            );
        } catch (InvalidArgumentException) {
            return $this->json(['error' => __d('projects', 'The change cannot be undone.')])->withStatus(422);
        }

        $stored = $this->fetchTable('Projects.Projects')->get($project->id);

        return $this->json([
            'kb_html' => (new KbRenderer())->render(
                KbDocument::parse((string)$stored->descript),
                (new KbCalendar())->linksFor($project, $this->getCurrentUser()),
            ),
            'kb_version' => (int)$stored->kb_version,
        ]);
    }

    /**
     * Add a deadline to the personal calendar of the user, or take it out again with `remove`.
     *
     * @param string|null $projectId Project id.
     * @return \Cake\Http\Response
     */
    public function calendar(?string $projectId = null): Response
    {
        $this->request->allowMethod('post');
        $project = $this->loadProject($projectId, 'knowledgeBaseCalendar');

        $itemId = (string)$this->request->getData('item_id');
        $remove = $this->request->getData('remove') === '1';
        $calendar = new KbCalendar();
        try {
            if ($remove) {
                $calendar->unsubscribe((string)$project->id, $itemId, (string)$this->getCurrentUser()->get('id'));
            } else {
                $calendar->subscribe((string)$project->id, $itemId, $this->getCurrentUser());
            }
        } catch (InvalidArgumentException) {
            return $this->json(['error' => __d('projects', 'The deadline cannot be added to the calendar.')])
                ->withStatus(422);
        }

        return $this->json(['linked' => !$remove]);
    }

    /**
     * @param string|null $projectId Project id.
     * @param string $action Policy action the user needs.
     * @return \Projects\Model\Entity\Project
     */
    private function loadProject(?string $projectId, string $action = 'knowledgeBase'): Project
    {
        /** @var \Projects\Model\Entity\Project $project */
        $project = $this->fetchTable('Projects.Projects')->get($projectId);
        $this->Authorization->authorize($project, $action);

        return $project;
    }

    /**
     * @param \Projects\Model\Entity\Project $project Project.
     * @param string $name Name of the value.
     * @return string
     */
    private function sessionKey(Project $project, string $name): string
    {
        return 'ProjectsKb.' . $project->id . '.' . $name;
    }

    /**
     * @param string $jobId Job id.
     * @return string
     */
    private function resultFile(string $jobId): string
    {
        return TMP . 'ai_jobs' . DS . $jobId . '_result.json';
    }

    /**
     * Remove results that nobody collected.
     *
     * @return void
     */
    private function removeOldResults(): void
    {
        $dir = TMP . 'ai_jobs' . DS;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        foreach (glob($dir . '*_result.json') ?: [] as $file) {
            if (filemtime($file) < time() - self::JOB_MAX_AGE) {
                unlink($file);
            }
        }
    }

    /**
     * @param array<string, mixed> $data Data.
     * @return \Cake\Http\Response
     */
    private function json(array $data): Response
    {
        return $this->response->withType('application/json')->withStringBody((string)json_encode($data));
    }
}
