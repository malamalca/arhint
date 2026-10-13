<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

class ProjectsAuthorityEvent extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'kind' => true,
        'event_date' => true,
        'parent_id' => true,
        'date_due' => true,
        'descript' => true,
    ];
}
