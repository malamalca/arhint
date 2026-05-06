<?php
declare(strict_types=1);

namespace Tasks\Model\Entity;

use App\Lib\AISerializableInterface;
use Cake\ORM\Entity;

/**
 * Task Entity.
 *
 * @property string $id
 * @property string $owner_id
 * @property string $folder_id
 * @property string $user_id
 * @property string $tasker_id
 * @property string $model
 * @property string $foreign_id
 * @property string $title
 * @property string $descript
 * @property \Cake\I18n\DateTime|null $started
 * @property \Cake\I18n\DateTime|null $deadline
 * @property \Cake\I18n\DateTime|null $completed
 * @property int $priority
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Task extends Entity implements AISerializableInterface
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
        '*' => true,
        'id' => false,
    ];

    /**
     * @inheritDoc
     */
    public function toAIArray(): array
    {
        return [
            'id' => $this->id,
            'folder_id' => $this->folder_id,
            'user_id' => $this->user_id,
            'tasker_id' => $this->tasker_id,
            'title' => $this->title,
            'descript' => $this->descript,
            'priority' => $this->priority,
            'started' => $this->started ? (string)$this->started : null,
            'deadline' => $this->deadline ? (string)$this->deadline : null,
            'completed' => $this->completed ? (string)$this->completed : null,
        ];
    }
}
