<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\Table;

class ProjectsAuthorityCalendarLinksTable extends Table
{
    /**
     * @param array<string, mixed> $config Configuration.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('projects_authority_calendar_links');
        $this->setPrimaryKey('id');
    }
}
