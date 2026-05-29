<?php
declare(strict_types=1);

namespace App\Event;

use App\Lib\AITool;
use App\Lib\QdrantSearchTool;
use ArrayObject;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\ORM\TableRegistry;

class AppAIToolsEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.registerModule' => 'aiAssistantRegisterModule',
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Register the App module for AI assistant module detection.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $modulesList Modules list to append to.
     * @return void
     */
    public function aiAssistantRegisterModule(Event $event, ArrayObject $modulesList): void
    {
        $modulesList['App'] = 'Core application tools: user management and company members.';
    }

    /**
     * Add AI assistant tools.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $toolsList List of tools.
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'App.get_users',
            arguments: [
                'active' => [
                    'type' => 'string',
                    'description' => '"true" to return only active users (default), "false" for inactive only, ' .
                        '"all" for all users.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Filter by name or username (case-insensitive partial match). Optional.',
                ],
            ],
            description: 'Lists users in the current company. Returns id, name, username, email, and active status.',
        ));

        $toolsList->append(new AITool(
            name: 'App.qdrant_search',
            arguments: [
                'query' => [
                    'type' => 'string',
                    'description' => 'Natural language question to answer using project intelligence logs. ' .
                        'e.g. "What is going on with the current project?" or "Are there any blockers?"',
                ],
                'entity_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the related entity to filter by '
                        . '(e.g. project UUID). Optional but recommended.',
                ],
            ],
            description: 'Searches project intelligence logs using semantic similarity and returns an AI-synthesized ' .
                'answer based on recent activity, risks, blockers, and status updates.',
        ));
    }

    /**
     * Execute AI assistant tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param string $tool Tool name.
     * @param array<mixed> $arguments Tool arguments.
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        if ($tool === 'App.get_users') {
            /** @var \App\Model\Table\UsersTable $usersTable */
            $usersTable = TableRegistry::getTableLocator()->get('Users');

            $query = $currentUser->applyScope('index', $usersTable->find())
                ->select(['Users.id', 'Users.name', 'Users.username', 'Users.email', 'Users.active'])
                ->orderBy(['Users.name' => 'ASC']);

            $activeArg = $arguments['active'] ?? 'true';
            if ($activeArg !== 'all') {
                $query->where(['Users.active' => $activeArg === 'false' ? 0 : 1]);
            }

            if (!empty($arguments['search'])) {
                $search = '%' . $arguments['search'] . '%';
                $query->where(['OR' => [
                    'Users.name LIKE' => $search,
                    'Users.username LIKE' => $search,
                ]]);
            }

            $event->setResult($query->all()->toArray());
        }

        if ($tool === 'App.qdrant_search') {
            $searchTool = new QdrantSearchTool($currentUser);

            // Build Qdrant filter if entity_id is provided.
            $filter = [];
            if (!empty($arguments['entity_id'])) {
                $filter = [
                    'must' => [[
                        'key' => 'log_foreign_id',
                        'match' => ['value' => (string)$arguments['entity_id']],
                    ]],
                ];
            }

            $result = $searchTool->searchAndAnalyze(
                query: (string)($arguments['query'] ?? ''),
                filter: $filter,
            );

            $event->setResult([
                'answer' => $result,
                'source' => 'Qdrant semantic intelligence search',
            ]);
        }
    }
}
