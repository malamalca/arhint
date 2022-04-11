<?php
declare(strict_types=1);

namespace Documents\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * DocumentsAttachments Model
 *
 * @method \Documents\Model\Entity\DocumentsAttachment get($primaryKey, array $options = [])
 * @method \Documents\Model\Entity\DocumentsAttachment newEmptyEntity()
 * @method \Documents\Model\Entity\DocumentsAttachment patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class DocumentsAttachmentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('documents_attachments');
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
            ->allowEmptyString('id', 'create')
            ->add('document_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('document_id')
            ->allowEmptyString('filename')
            ->allowEmptyString('original')
            ->allowEmptyString('ext')
            ->allowEmptyString('mimetype')
            ->add('filesize', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('filesize')
            ->add('height', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('height')
            ->add('width', 'valid', ['rule' => 'numeric'])
            ->allowEmptyString('width')
            ->allowEmptyString('title')
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
     * beforeMarshal method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $data Post data.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (!empty($data['filename']) && is_array($data['filename'])) {
            $fileData = $data['filename'];
            $data['filename'] = uniqid('');
            $data['original'] = $fileData['name'];
            $data['mime'] = $fileData['type'];
            $data['filesize'] = $fileData['size'];
        }
        if (!empty($data['scanned'])) {
            $data['filename'] = uniqid('');
            $data['original'] = 'scanned.pdf';
            $data['mime'] = 'application/pdf';
            $data['filesize'] = strlen(base64_encode($data['scanned']));
        }
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $InvoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
        $attachmentCount = $this->find()->where(['document_id' => $entity->document_id])->count();
        $InvoicesTable->updateAll(['attachments_count' => $attachmentCount], ['id' => $entity->document_id]);

        if (
            !empty($options['uploadedFilename']) &&
            !empty($entity->filename) &&
            file_exists($options['uploadedFilename'][$entity->original])
        ) {
            $fileDest = Configure::read('Documents.uploadFolder') . DS . $entity->filename;
            $moved = copy($options['uploadedFilename'][$entity->original], $fileDest);
            //unlink($options['uploadedFilename']);
        }
    }

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $fileName = (string)Configure::read('Documents.uploadFolder') . DS . $entity->filename;
        if (file_exists($fileName) && is_file($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param \Documents\Model\Entity\DocumentsAttachment $entity Entity.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entity, $ownerId)
    {
        switch ($entity->model) {
            case 'Document':
                /** @var \Documents\Model\Table\DocumentsTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Documents');
                break;
            case 'TravelOrder':
                /** @var \Documents\Model\Table\TravelOrdersTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');
                break;
            default:
                /** @var \Documents\Model\Table\InvoicesTable $ModelTable */
                $ModelTable = TableRegistry::getTableLocator()->get('Documents.Invoices');
        }

        return $ModelTable->isOwnedBy($entity->document_id, $ownerId);
    }
}
