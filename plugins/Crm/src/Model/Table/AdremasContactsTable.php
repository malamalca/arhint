<?php
declare(strict_types=1);

namespace Crm\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Laminas\Diactoros\UploadedFile;

/**
 * AdremasContacts Model
 *
 * @property \Crm\Model\Table\AdremasTable $Adremas
 * @method \Crm\Model\Entity\AdremasContact newEmptyEntity()
 * @method \Crm\Model\Entity\AdremasContact newEntity(array $data, array $options = [])
 * @method \Crm\Model\Entity\AdremasContact get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \Crm\Model\Entity\AdremasContact patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class AdremasContactsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('adremas_contacts');
        $this->setPrimaryKey('id');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Adremas', [
            'foreignKey' => 'adrema_id',
            'className' => 'Crm.Adremas',
        ]);
        $this->belongsTo('Contacts', [
            'foreignKey' => 'contact_id',
            'className' => 'Crm.Contacts',
        ]);
        $this->belongsTo('ContactsAddresses', [
            'foreignKey' => 'contacts_address_id',
            'className' => 'Crm.ContactsAddresses',
        ]);
        $this->belongsTo('ContactsEmails', [
            'foreignKey' => 'contacts_email_id',
            'className' => 'Crm.ContactsEmails',
        ]);
        $this->hasMany('Attachments', [
            'foreignKey' => 'foreign_id',
            'conditions' => ['model' => 'AdremasContact'],
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
            //->add('id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('id', 'create')
            //->add('owner_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('owner_id')
            ->add('adrema_id', 'valid', ['rule' => 'uuid'])
            ->notEmptyString('adrema_id')
            ->notEmptyString('contact_id')
            //->add('contacts_address_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('contacts_address_id');

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
        $rules->add($rules->existsIn(['adrema_id'], 'Adremas'));
        $rules->add($rules->existsIn(['contact_id'], 'Contacts'));
        $rules->add($rules->existsIn(['contacts_address_id'], 'ContactsAddresses'));
        $rules->add($rules->existsIn(['contacts_email_id'], 'ContactsEmails'));

        return $rules;
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
        /** @var \Crm\Model\Entity\AdremasContact $entity */
        $entity = $this->get($entityId, ['fields' => 'adrema_id']);

        return $this->Adremas->isOwnedBy($entity->adrema_id, $ownerId);
    }

    /**
     * beforeSave method
     *
     * @param \Cake\Event\EventInterface $event The beforeSave event.
     * @param \Cake\Datasource\EntityInterface $entity The entity being saved.
     * @param \ArrayObject<string, mixed> $options The options passed to the save method.
     * @return bool
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): bool
    {
        /** @var \Crm\Model\Entity\AdremasContact $entity */
        if (isset($entity->data) && is_array($entity->data)) {
            $entity->descript = json_encode($entity->data, JSON_THROW_ON_ERROR);
        }

        return true;
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
        /** @var \Crm\Model\Entity\AdremasContact $entity */
        if (isset($entity->data) && is_array($entity->data)) {
            /** @var \App\Model\Table\AttachmentsTable $AttachmentsTable */
            $AttachmentsTable = TableRegistry::getTableLocator()->get('App.Attachments');

            foreach ($entity->data as $fieldName => $fileData) {
                if ($fileData instanceof UploadedFile && $fileData->getError() === UPLOAD_ERR_OK) {
                    $existingAttachment = $AttachmentsTable->find()
                        ->where(['model' => 'AdremasContact','foreign_id' => $entity->get('id')])
                        ->first();
                    if ($existingAttachment) {
                        $AttachmentsTable->delete($existingAttachment);
                    }

                    $attachment = $AttachmentsTable->newEmptyEntity();
                    $attachment->foreign_id = $entity->get('id');
                    $attachment->model = 'AdremasContact';
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
                            ['descript' => json_encode($data, JSON_THROW_ON_ERROR)],
                            ['id' => $entity->id],
                        );
                    }
                }
            }
        }
    }
}
