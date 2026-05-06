<?php
declare(strict_types=1);

namespace App\Event;

use App\Lib\AITool;
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
    }
}
