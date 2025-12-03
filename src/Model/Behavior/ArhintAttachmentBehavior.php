<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Laminas\Diactoros\UploadedFile;
use ReflectionClass;

class ArhintAttachmentBehavior extends Behavior
{
    protected array $_defaultConfig = [
        'field' => 'filename',
    ];

    /**
     * After Save Event
     *
     * @param \Cake\Event\Event $event Event
     * @param \Cake\Datasource\EntityInterface $entity Entity
     * @param \ArrayObject $options Options
     * @return void
     */
    public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options): void
    {
        $config = $this->getConfig();

        $processFiles = [];
        if ($config['field'] === '*') {
            foreach ($options['uploadedFiles'] as $fieldName => $field) {
                if ($field instanceof UploadedFile) {
                    $processFiles[$fieldName] = $field;
                }
            }
        } else {
            foreach ((array)$config['field'] as $field) {
                if (
                    isset($options['uploadedFiles'][$field]) &&
                    $options['uploadedFiles'][$field] instanceof UploadedFile
                ) {
                    $processFiles[$field] = $options['uploadedFiles'][$field];
                }
            }
        }

        /** @var \App\Model\Table\AttachmentsTable $AttachmentsTable */
        $AttachmentsTable = TableRegistry::getTableLocator()->get('Attachments');

        $reflect = new ReflectionClass($entity);

        foreach ($processFiles as $fileData) {
            if ($fileData->getError() === UPLOAD_ERR_OK) {
                $attachment = $AttachmentsTable->newEmptyEntity();
                $attachment->model = $reflect->getShortName();
                $attachment->foreign_id = $entity->get('id');

                $attachment->filename = $fileData->getClientFilename();
                $attachment->mimetype = $fileData->getClientMediaType();
                $attachment->filesize = $fileData->getSize();

                if (is_string($attachment->filename)) {
                    $AttachmentsTable->save(
                        $attachment,
                        ['uploadedFilename' => [
                            $attachment->filename => $fileData->getStream()->getMetadata('uri'),
                        ]],
                    );
                }
            }
        }
    }
}
