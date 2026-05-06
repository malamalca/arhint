<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\ORM\Entity;

/**
 * ProjectsLog Entity
 *
 * @property string $id
 * @property string|null $project_id
 * @property string|null $user_id
 * @property string|null $descript
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Projects\Model\Entity\Project $project
 * @property \Projects\Model\Entity\User $user
 */
class ProjectsLog extends Entity implements AISerializableInterface
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'project_id' => true,
        'user_id' => true,
        'descript' => true,
        'created' => true,
        'modified' => true,
        'project' => true,
        'user' => true,
    ];

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'descript' => $this->descript,
            'created' => $this->created ? (string)$this->created : null,
        ];
    }
}
