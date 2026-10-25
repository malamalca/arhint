<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsKbRevision Entity
 *
 * A state of the knowledge base of a project, as it was after the change of that version.
 *
 * @property string $id
 * @property string $project_id
 * @property string|null $user_id
 * @property int $version
 * @property string $source
 * @property string|null $summary
 * @property string|null $operations
 * @property string|null $descript
 * @property \Cake\I18n\DateTime|null $created
 */
class ProjectsKbRevision extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'project_id' => true,
        'user_id' => true,
        'version' => true,
        'source' => true,
        'summary' => true,
        'operations' => true,
        'descript' => true,
    ];
}
