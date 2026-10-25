<?php
declare(strict_types=1);

namespace Projects\Event;

use App\Lib\AIAssistant;
use App\Lib\AITool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Projects\Lib\KnowledgeBase\KbDocument;
use Projects\Lib\KnowledgeBase\KbOperationApplier;
use Projects\Lib\KnowledgeBase\KbPrompt;
use Projects\Lib\KnowledgeBase\KbService;
use Projects\Lib\KnowledgeBase\StaleKnowledgeBaseException;
use Throwable;

/**
 * Tools for the conversation about the knowledge base of one project.
 *
 * The tools exist only for an assistant created with the project in its context (`kb_project_id`), and they work
 * on that project only: the model cannot name another one.
 */
class KnowledgeBaseAIToolsEvents implements EventListenerInterface
{
    /** Key of the context of AIAssistant that names the project of the conversation. */
    public const CONTEXT_PROJECT = 'kb_project_id';

    /** The tools a conversation about the knowledge base may use. */
    public const CHAT_TOOLS = [
        'Projects.kb_read',
        'Projects.kb_apply',
        'Projects.get_project_tasks',
        'Projects.get_project_logs',
        'Projects.get_project_documents',
        'Projects.get_project_users',
        'App.vector_search',
        'App.list_attachments',
        'App.read_attachment',
    ];

    /**
     * @return array<string, string>
     */
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Offer the tools to a conversation about a project.
     *
     * @param \Cake\Event\Event $event Event.
     * @param \ArrayObject $toolsList List of tools.
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        if ($this->projectId($event) === null) {
            return;
        }

        $toolsList->append(new AITool(
            name: 'Projects.kb_read',
            arguments: [
                'section' => [
                    'type' => 'string',
                    'description' => 'Id of a section (summary, description, warnings, deadlines, parties, '
                        . 'procedures, decisions, open_questions, documents, notes). Omit for all of them.',
                ],
            ],
            description: 'Reads the knowledge base of the project: one section or all of them. Items carry ids.',
        ));
        $toolsList->append(new AITool(
            name: 'Projects.kb_apply',
            arguments: [
                'operations' => [
                    'type' => 'array',
                    'description' => 'List of operations as described in the instructions.',
                ],
                'changes_summary' => [
                    'type' => 'string',
                    'description' => 'One short sentence on the change, for the changelog.',
                ],
            ],
            description: 'Changes the knowledge base of the project with a list of operations. Returns what was '
                . 'applied and which operations were rejected.',
        ));
    }

    /**
     * Execute a knowledge base tool.
     *
     * @param \Cake\Event\Event $event Event.
     * @param string $tool Tool name.
     * @param array<mixed> $arguments Arguments.
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        if ($tool !== 'Projects.kb_read' && $tool !== 'Projects.kb_apply') {
            return;
        }

        $projectId = $this->projectId($event);
        $user = $event->getData()[2] ?? null;
        if ($projectId === null || $user === null) {
            $event->setResult(['error' => 'The knowledge base tools are not available here.']);

            return;
        }

        try {
            /** @var \Projects\Model\Entity\Project $project */
            $project = TableRegistry::getTableLocator()->get('Projects.Projects')->get($projectId);
            if (!$user->can('knowledgeBase', $project)) {
                $event->setResult(['error' => 'You do not have access to this project.']);

                return;
            }

            $event->setResult($tool === 'Projects.kb_read'
                ? $this->read($project, $arguments)
                : $this->apply($project, $arguments, (string)$user->get('id')));
        } catch (StaleKnowledgeBaseException) {
            $event->setResult(['error' => 'The knowledge base was changed in the meantime. Read it again and retry.']);
        } catch (Throwable $e) {
            Log::error('Knowledge base tool failed: ' . get_class($e) . ': ' . $e->getMessage(), ['scope' => 'ai']);
            $event->setResult(['error' => $e->getMessage()]);
        }
    }

    /**
     * @param \Projects\Model\Entity\Project $project Project.
     * @param array<mixed> $arguments Arguments.
     * @return array<string, mixed>
     */
    private function read(object $project, array $arguments): array
    {
        $doc = (new KbService())->document($project);
        $section = trim((string)($arguments['section'] ?? ''));

        if ($section === '') {
            return ['version' => (int)$project->kb_version, 'knowledge_base' => (new KbPrompt())->state($doc)];
        }
        if (isset(KbDocument::ITEM_SECTIONS[$section])) {
            return ['section' => $section, 'items' => array_map(fn($item): array => array_filter([
                'id' => $item->id,
                'text' => $item->text,
                'date' => $item->date,
                'severity' => $section === 'warnings' ? $item->severity : null,
                'status' => $item->status,
            ], fn($value): bool => $value !== null), $doc->items($section))];
        }
        if (in_array($section, KbDocument::TEXT_SECTIONS, true)) {
            return ['section' => $section, 'text' => $doc->text($section)];
        }

        return ['error' => "Unknown section '$section'."];
    }

    /**
     * @param \Projects\Model\Entity\Project $project Project.
     * @param array<mixed> $arguments Arguments.
     * @param string $userId User of the conversation.
     * @return array<string, mixed>
     */
    private function apply(object $project, array $arguments, string $userId): array
    {
        $operations = $arguments['operations'] ?? null;
        if (is_string($operations)) {
            $operations = json_decode($operations, true);
        }
        if (!is_array($operations) || $operations === []) {
            return ['error' => 'operations must be a non-empty list.'];
        }
        // A single operation is sometimes sent without the list around it.
        if (isset($operations['op'])) {
            $operations = [$operations];
        }

        $write = (new KbService())->apply(
            (string)$project->id,
            array_values($operations),
            KbOperationApplier::CHAT,
            $userId,
            null,
            trim((string)($arguments['changes_summary'] ?? '')) ?: null,
        );

        $result = [
            'applied' => $write->result->applied,
            'rejected' => $write->result->rejected,
        ];
        if ($write->revision === null) {
            return ['error' => 'Nothing was changed.'] + $result;
        }

        return $result + ['changes' => $write->result->descriptions, 'version' => $write->revision->version];
    }

    /**
     * @param \Cake\Event\Event $event Event of the tool.
     * @return string|null Project of the conversation.
     */
    private function projectId(Event $event): ?string
    {
        $assistant = $event->getSubject();
        if (!$assistant instanceof AIAssistant) {
            return null;
        }
        $id = $assistant->getContext()[self::CONTEXT_PROJECT] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }
}
