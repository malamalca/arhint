<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

class ProjectsAuthorityCase extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'contact_id' => true,
        'title' => true,
        'user_id' => true,
        'reference' => true,
        'descript' => true,
        'exempt_reason' => true,
    ];
}
