<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * DocumentsTemplates Model
 *
 * @method \Documents\Model\Entity\DocumentsTemplate get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Documents\Model\Entity\DocumentsTemplate newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsTemplate patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsTemplatesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_templates');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            ->add('owner_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('owner_id')
            ->notEmptyString('kind')
            ->notEmptyString('title')
            ->allowEmptyString('body');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        //$rules->add($rules->existsIn(['user_id'], 'Users'));
        return $rules;
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Documents\Model\Entity\DocumentsTemplate $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return bool
     */
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options): bool
    {
        if (
            !$this->exists([
            'owner_id' => $entity->owner_id,
            'main' => true,
            'kind' => $entity->kind,
            ])
        ) {
            $entity->main = true;
        }

        return true;
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Documents\Model\Entity\DocumentsTemplate $entity Entity object.
     * @param \ArrayObject $options Options array.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if ($entity->main) {
            $this->updateAll(['main' => false], [
                'owner_id' => $entity->owner_id,
                'NOT' => [
                    'id' => $entity->id,
                    'kind' => $entity->kind,
                ],
            ]);
        }
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(string $entityId, string $ownerId): bool
    {
        return $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * List templates by kind for specified owner.
     *
     * @param string $ownerId User Id.
     * @return array<\Documents\Model\Entity\DocumentsTemplate>
     */
    public function findForOwner(string $ownerId): array
    {
        // In a controller or table method.
        $query = $this->find('list', [

            'keyField' => 'id',
            'valueField' => 'title',
            'groupField' => 'kind',
        ])->where(['owner_id' => $ownerId]);
        $data = $query->toArray();

        return $data;
    }
}
