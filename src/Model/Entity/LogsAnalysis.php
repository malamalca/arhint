<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * LogsAnalysis Entity
 *
 * @property string $id
 * @property string $event_id
 * @property string|null $summary
 * @property string|null $risks
 * @property string|null $blockers
 * @property int|null $priority
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class LogsAnalysis extends Entity
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
        'event_id' => true,
        'summary' => true,
        'risks' => true,
        'blockers' => true,
        'priority' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Fields that are automatically converted to their appropriate types.
     *
     * @var array<string, string>
     */
    protected array $_cast = [
        'priority' => 'integer',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];
}
