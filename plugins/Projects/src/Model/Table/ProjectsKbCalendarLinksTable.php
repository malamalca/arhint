<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\Table;

/**
 * ProjectsKbCalendarLinks Model
 *
 * @method \Projects\Model\Entity\ProjectsKbCalendarLink newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsKbCalendarLink newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsKbCalendarLink|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsKbCalendarLinksTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects_kb_calendar_links');
        $this->setDisplayField('item_id');
        $this->setPrimaryKey('id');

        // Links are never changed after they are made, apart from the calendar entry they manage.
        $this->addBehavior('Timestamp', [
            'events' => ['Model.beforeSave' => ['created' => 'new']],
        ]);
    }
}
