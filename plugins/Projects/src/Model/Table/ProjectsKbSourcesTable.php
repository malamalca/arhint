<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\Table;

/**
 * ProjectsKbSources Model
 *
 * @method \Projects\Model\Entity\ProjectsKbSource newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsKbSource newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsKbSource|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsKbSourcesTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects_kb_sources');
        $this->setDisplayField('source_id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.Projects',
        ]);
    }
}
