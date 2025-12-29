<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Laminas\Diactoros\UploadedFile;

/**
 * Adremas Model
 *
 * @method \Crm\Model\Entity\Adrema newEmptyEntity()
 * @method \Crm\Model\Entity\Adrema newEntity(array $data, array $options = [])
 * @method \Crm\Model\Entity\Adrema get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Crm\Model\Entity\Adrema patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class AdremasTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('adremas');
        $this->setDisplayField('title');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->hasMany('FormAttachments', [
            'foreignKey' => 'foreign_id',
            'conditions' => ['model LIKE' => 'Adrema.%'],
            'dependant' => true,
            'className' => 'App\Model\Table\AttachmentsTable',
        ]);
        $this->hasMany('Attachments', [
            'foreignKey' => 'foreign_id',
            'conditions' => ['model' => 'Adrema'],
            'dependant' => true,
        ]);
        $this->belongsToMany('Contacts', [
            'foreignKey' => 'adrema_id',
            'targetForeignKey' => 'contact_id',
            'joinTable' => 'adremas_contacts',
            'className' => 'Crm\Model\Table\ContactsTable',
            'dependant' => true,
        ]);
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
            ->allowEmptyString('title');

        return $validator;
    }

    /**
     * Before email validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationEmail(Validator $validator): Validator
    {
        $validator
            ->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            ->allowEmptyString('title');

        return $validator;
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string|null $entityId Entity Id.
     * @param string|null $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(?string $entityId, ?string $ownerId): bool
    {
        return !empty($entityId) && !empty($ownerId) && $this->exists(['id' => $entityId, 'owner_id' => $ownerId]);
    }

    /**
     * copyAddresses method
     *
     * @param string $sourceId Source's adrema id.
     * @param string $destId Destination's adrema id.
     * @return void
     */
    public function copyAddresses(string $sourceId, string $destId): void
    {
        $AdremasContacts = TableRegistry::getTableLocator()->get('Crm.AdremasContacts');

        $addresses = $AdremasContacts
            ->find()
            ->where(['adrema_id' => $sourceId])
            ->all();

        foreach ($addresses as $address) {
            /** @var \Crm\Model\Entity\AdremasContact $new */
            $new = $AdremasContacts->newEmptyEntity();
            $AdremasContacts->patchEntity($new, $address->toArray());

            $new->adrema_id = $destId;

            $AdremasContacts->save($new);
        }
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event.
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved.
     * @param \ArrayObject<string, mixed> $options The options passed to the save method.
     * @return void
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        /** @var \Crm\Model\Entity\Adrema $entity */
        if (isset($entity->data) && is_array($entity->data)) {
            $entity->user_values = json_encode($entity->data, JSON_THROW_ON_ERROR);
        }
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\EventInterface $event The afterSave event.
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved.
     * @param \ArrayObject<string, mixed> $options The options passed to the save method.
     * @return void
     */
    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        /** @var \Crm\Model\Entity\Adrema $entity */
        if (isset($entity->data) && is_array($entity->data)) {
            /** @var \App\Model\Table\AttachmentsTable $AttachmentsTable */
            $AttachmentsTable = TableRegistry::getTableLocator()->get('App.Attachments');

            foreach ($entity->data as $fieldName => $fileData) {
                if ($fileData instanceof UploadedFile && $fileData->getError() === UPLOAD_ERR_OK) {
                    $AttachmentsTable->deleteAll([
                        'model' => 'Adrema.' . $fieldName,
                        'foreign_id' => $entity->get('id'),
                    ]);

                    $attachment = $AttachmentsTable->newEmptyEntity();
                    $attachment->model = 'Adrema.' . $fieldName;
                    $attachment->foreign_id = $entity->get('id');
                    $attachment->filename = $fileData->getClientFilename();
                    $attachment->mimetype = $fileData->getClientMediaType();
                    $attachment->filesize = $fileData->getSize();

                    $result = $AttachmentsTable->save(
                        $attachment,
                        ['uploadedFilename' => [(string)$fileData->getClientFilename() => $fileData]],
                    );
                    if ($result) {
                        $data = $entity->data;
                        $data[$fieldName] = $attachment->id;
                        $this->updateAll(
                            ['user_values' => json_encode($data, JSON_THROW_ON_ERROR)],
                            ['id' => $entity->id],
                        );
                    }
                }
            }
        }
    }
}
