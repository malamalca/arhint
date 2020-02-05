<?php
declare(strict_types=1);

namespace LilInvoices\Model\Table;

use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

/**
 * InvoicesAttachments Model
 *
 * @method \LilInvoices\Model\Entity\InvoicesAttachment get(string $id)
 * @method \LilInvoices\Model\Entity\InvoicesAttachment newEmptyEntity()
 * @method \LilInvoices\Model\Entity\InvoicesAttachment patchEntity($entity, array $data = [])
 */
class InvoicesAttachmentsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        $this->setTable('invoices_attachments');
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
            ->allowEmptyString('model')
            ->add('foreign_id', 'valid', ['rule' => 'uuid'])
            ->allowEmptyString('foreign_id')
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
    }

    /**
     * afterSave method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilInvoices\Model\Entity\InvoicesAttachment $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $invoices = TableRegistry::get('LilInvoices.Invoices');
        $attachmentCount = $this->find()->where(['foreign_id' => $entity->foreign_id])->count();
        $invoices->updateAll(['invoices_attachment_count' => $attachmentCount], ['id' => $entity->foreign_id]);

        if (!empty($options['uploadedFilename'])) {
            $fileDest = Configure::read('LilInvoices.uploadFolder') . DS . $entity->filename;
            $moved = copy($options['uploadedFilename'], $fileDest);
        }
    }

    /**
     * afterDelete method
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \LilInvoices\Model\Entity\InvoicesAttachment $entity Entity object.
     * @param \ArrayObject $options Array object.
     * @return void
     */
    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        $fileName = (string)Configure::read('LilInvoices.uploadFolder') . DS . $entity->filename;
        if (file_exists($fileName)) {
            unlink($fileName);
        }
    }

    /**
     * Checks if entity belongs to user.
     *
     * @param string $entityId Entity Id.
     * @param string $ownerId User Id.
     * @return bool
     */
    public function isOwnedBy($entityId, $ownerId)
    {
        /** @var \LilInvoices\Model\Entity\InvoicesAttachment $entity */
        $entity = $this->find()
            ->where(['model' => 'Invoice'])
            ->first();

        /** @var \LilInvoices\Model\Table\InvoicesTable $InvoicesTable */
        $InvoicesTable = TableRegistry::getTableLocator()->get('LilInvoices.Invoices');

        return $InvoicesTable->isOwnedBy($entity->foreign_id, $ownerId);
    }
}
