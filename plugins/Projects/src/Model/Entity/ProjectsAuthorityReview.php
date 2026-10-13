<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

class ProjectsAuthorityReview extends Entity
{
    /**
     * Reviews are created from a server-side snapshot only.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [];
}
