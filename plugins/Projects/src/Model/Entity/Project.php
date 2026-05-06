<?php
declare(strict_types=1);

namespace Projects\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\ORM\Entity;

/**
 * Project Entity
 *
 * @property string $id
 * @property string $owner_id
 * @property string|null $status_id
 * @property string $no
 * @property string $title
 * @property string $descript
 * @property float $lat
 * @property float $lon
 * @property string $ico
 * @property string $colorize
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \Projects\Model\Entity\Owner $owner
 * @property \Projects\Model\Entity\ProjectsStatus|null $projects_status
 * @property \Projects\Model\Entity\ProjectsMilestone[] $projects_milestones
 */
class Project extends Entity implements AISerializableInterface
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
        'owner_id' => true,
        'status_id' => true,
        'no' => true,
        'title' => true,
        'descript' => true,
        'lat' => true,
        'lon' => true,
        'ico' => true,
        'colorize' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'owner' => true,
    ];

    /**
     * Returns friendly project name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->no . ' - ' . $this->title;
    }

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->no . ' - ' . $this->title;
    }

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'no' => $this->no,
            'title' => $this->title,
            'active' => $this->active,
            'view_url' => $this->view_url ?? null,
            'milestones' => array_map(fn($e) => $e->toAIArray(), $this->projects_milestones ?? []),
            'status' => $this->projects_status?->toAIArray(),
        ];
    }
}
