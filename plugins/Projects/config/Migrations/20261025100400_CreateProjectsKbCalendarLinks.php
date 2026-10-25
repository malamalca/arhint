<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateProjectsKbCalendarLinks extends BaseMigration
{
    /**
     * A user's opt-in to see a deadline of the knowledge base in the personal calendar.
     *
     * Calendar is migrated separately, so cross-plugin identifiers have no foreign keys.
     * `event_id` is the managed calendar entry; it is only set while the deadline is open.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects_kb_calendar_links', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('project_id', 'uuid')
            ->addColumn('item_id', 'string', ['limit' => 20])
            ->addColumn('calendar_id', 'uuid')
            ->addColumn('event_id', 'uuid', ['null' => true, 'default' => null])
            ->addColumn('created', 'datetime', ['null' => true, 'default' => null])
            ->addIndex(['project_id', 'item_id', 'calendar_id'], ['unique' => true])
            ->addIndex(['event_id'], ['unique' => true])
            ->addIndex(['calendar_id'])
            ->create();
    }
}
