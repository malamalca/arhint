<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

class ProjectsAuthorityEventDocument extends Entity
{
    /**
     * Document links are assigned by the procedure controller only.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
