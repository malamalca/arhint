<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsKbCalendarLink Entity
 *
 * @property string $id
 * @property string $project_id
 * @property string $item_id Id of the deadline in the knowledge base of the project
 * @property string $calendar_id User whose personal calendar holds the entry
 * @property string|null $event_id Managed calendar entry
 * @property \Cake\I18n\DateTime|null $created
 */
class ProjectsKbCalendarLink extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'project_id' => true,
        'item_id' => true,
        'calendar_id' => true,
        'event_id' => true,
    ];
}
