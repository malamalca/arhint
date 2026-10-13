<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateAuthorityCalendarLinks extends BaseMigration
{
    /**
     * Calendar is migrated separately, so cross-plugin identifiers have no foreign keys.
     *
     * @return void
     */
    public function change(): void
    {
        $this->table('projects_authority_calendar_links', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('case_id', 'uuid')
            ->addColumn('calendar_id', 'uuid')
            ->addColumn('event_id', 'uuid', ['null' => true])
            ->addColumn('source_event_id', 'uuid', ['null' => true])
            ->addIndex(['case_id', 'calendar_id'], ['unique' => true])
            ->addIndex(['event_id'], ['unique' => true])
            ->addIndex(['calendar_id'])
            ->create();
    }
}
