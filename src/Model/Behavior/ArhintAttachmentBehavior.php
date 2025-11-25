<?php
declare(strict_types=1);

namespace App\Model\Behavior;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Laminas\Diactoros\UploadedFile;
use ReflectionClass;

class ArhintAttachmentBehavior extends Behavior
{
    protected array $_defaultConfig = [
        'field' => 'filename',
    ];

    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
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
                if (isset($options['uploadedFiles'][$field]) && $options['uploadedFiles'][$field] instanceof UploadedFile) {
                    $processFiles[$field] = $data['data'][$field];
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
                $attachment->foreign_id = $entity->id;

                $attachment->filename = $fileData->getClientFilename();
                $attachment->mimetype = $fileData->getClientMediaType();
                $attachment->filesize = $fileData->getSize();

                $AttachmentsTable->save($attachment, ['uploadedFilename' => [$attachment->filename => $fileData->getStream()->getMetadata('uri')]]);
            }
        }
    }
}
