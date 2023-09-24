<?php
declare(strict_types=1);

namespace Calendar\Model\Entity;

use Cake\ORM\Entity;

/**
 * Event Entity
 *
 * @property string $id
 * @property string|null $calendar_id
 * @property string|null $title
 * @property string|null $location
 * @property string|null $body
 * @property bool $all_day
 * @property \Cake\I18n\DateTime|null $dat_start
 * @property \Cake\I18n\DateTime|null $dat_end
 * @property int|null $reminder
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Contacts\Model\Entity\Contact $calendar
 */
class Event extends Entity
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
        'calendar_id' => true,
        'title' => true,
        'location' => true,
        'body' => true,
        'all_day' => true,
        'dat_start' => true,
        'dat_end' => true,
        'reminder' => true,
        'created' => true,
        'modified' => true,
        'owner' => true,
        'user' => true,
    ];
}
