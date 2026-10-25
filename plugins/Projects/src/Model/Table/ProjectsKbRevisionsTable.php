<?php
declare(strict_types=1);

namespace Projects\Model\Table;

use Cake\ORM\Table;

/**
 * ProjectsKbRevisions Model
 *
 * @method \Projects\Model\Entity\ProjectsKbRevision newEmptyEntity()
 * @method \Projects\Model\Entity\ProjectsKbRevision newEntity(array $data, array $options = [])
 * @method \Projects\Model\Entity\ProjectsKbRevision get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Projects\Model\Entity\ProjectsKbRevision|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ProjectsKbRevisionsTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('projects_kb_revisions');
        $this->setDisplayField('version');
        $this->setPrimaryKey('id');

        // Revisions are never changed after they are written.
        $this->addBehavior('Timestamp', [
            'events' => ['Model.beforeSave' => ['created' => 'new']],
        ]);

        $this->belongsTo('Projects', [
            'foreignKey' => 'project_id',
            'className' => 'Projects.Projects',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'className' => 'Users',
        ]);
    }
}
