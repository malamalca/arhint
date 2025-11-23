<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Attachment;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * Attachments Model
 *
 * @method \App\Model\Entity\Attachment get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\Attachment newEmptyEntity()
 * @method \App\Model\Entity\Attachment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class AttachmentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config List of options for this table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('attachments');
        $this->setDisplayField('filename');
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
            ->allowEmptyString('id', 'create')
            ->add('document_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('document_id')
            ->allowEmptyString('filename')
            ->allowEmptyString('ext')
            ->allowEmptyString('mimetype')
            ->add('filesize', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('filesize')
            ->allowEmptyString('description');

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
        return $rules;
    }

    /**
     * FINDER function for attachemts.
     *
     * @param \Cake\ORM\Query\SelectQuery $q TSelect query.
     * @param string $model Model name.
     * @param string $foreignId Foreign id (id from model table).
     * @return \Cake\ORM\Query\SelectQuery
     */
    public function findForModel(SelectQuery $q, string $model, string $foreignId): SelectQuery
    {
        $q->where(['model' => $model, 'foreign_id' => $foreignId]);

        return $q;
    }

    /**
     * beforeMarshal method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $data Post data.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options): void
    {
        if (!empty($data['filename']) && is_array($data['filename'])) {
            $fileData = $data['filename'];
            $data['filename'] = $fileData['name'];
            $data['mimetype'] = $fileData['type'];
            $data['filesize'] = $fileData['size'];
        }
        if (!empty($data['filename']) && is_a($data['filename'], '\Laminas\Diactoros\UploadedFile')) {
            $fileData = $data['filename'];
            $data['filename'] = $fileData->getClientFilename();
            $data['mimetype'] = $fileData->getClientMediaType();
            $data['filesize'] = $fileData->getSize();
        }
        if (!empty($data['scanned'])) {
            $data['filename'] = uniqid('');
            $data['original'] = 'scanned.pdf';
            $data['mimetype'] = 'application/pdf';
            $data['filesize'] = strlen(base64_encode($data['scanned']));
        }
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \App\Model\Entity\Attachment $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if (
            !empty($options['uploadedFilename']) &&
            !empty($entity->filename) &&
            file_exists($options['uploadedFilename'][$entity->filename])
        ) {
            $folderDest = Configure::read('App.uploadFolder') . DS . $entity->model . DS;
            if (!file_exists($folderDest)) {
                mkdir($folderDest, 0777);
            }
            $fileDest = $folderDest . $entity->filename;
            copy($options['uploadedFilename'][$entity->filename], $fileDest);
            unlink($options['uploadedFilename'][$entity->filename]);
        }
    }

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \App\Model\Entity\Attachment $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options): void
    {
        $fileName = (string)Configure::read('App.uploadFolder') . DS . $entity->model . DS . $entity->filename;
        if (file_exists($fileName) && is_file($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param \App\Model\Entity\Attachment $entity Entity.
     * @param string|null $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy(Attachment $entity, ?string $ownerId): bool
    {
        if (empty($ownerId)) {
            return false;
        }

        switch ($entity->model) {
            case 'Document':
                /** @var \Documents\Model\Table\DocumentsTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Documents');
                break;
            case 'Invoice':
                /** @var \Documents\Model\Table\InvoicesTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
                break;
            case 'TravelOrder':
                /** @var \Documents\Model\Table\TravelOrdersTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
                break;
            default:
                /** @var \Documents\Model\Table\InvoicesTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
        }

        return !$ModelTable->exists(['id' => $entity->foreign_id]) ||
            $ModelTable->isOwnedBy($entity->foreign_id, $ownerId);
    }
}
