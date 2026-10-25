<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use Cake\ORM\Entity;

/**
 * ProjectsKbSource Entity
 *
 * @property string $id
 * @property string $project_id
 * @property string $source_model
 * @property string $source_id
 * @property \Cake\I18n\DateTime|null $source_modified
 * @property string|null $revision_id
 * @property string $status
 * @property int $attempts
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class ProjectsKbSource extends Entity
{
    public const APPLIED = 'applied';
    public const SKIPPED = 'skipped';
    public const FAILED = 'failed';

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'project_id' => true,
        'source_model' => true,
        'source_id' => true,
        'source_modified' => true,
        'revision_id' => true,
        'status' => true,
        'attempts' => true,
    ];
}
