<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;

class GlobalAttachmentsCommand extends Command
{
    /**
     * Hourly heartbeat function
     *
     * @param \Cake\Console\Arguments $args Console arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $AttachmentsTable = $this->fetchTable('Attachments');

        $attachments = $this->fetchTable('Documents.DocumentsAttachments')->find()
            ->select()
            ->all();

        foreach ($attachments as $documentAttachment) {
            /** @var \App\Model\Entity\Attachment $documentAttachment */

            $filePath = Configure::read('App.uploadFolder') . 'Documents' . DS . $documentAttachment->filename;
            if (file_exists($filePath)) {
                $io->out($documentAttachment->original);

                $targetFolder = Configure::read('App.uploadFolder') . $documentAttachment->model . DS;
                if (!file_exists($targetFolder)) {
                    mkdir($targetFolder, 0777, true);
                }

                /** @var \App\Model\Entity\Attachment $globalAttachment */
                $globalAttachment = $AttachmentsTable->newEmptyEntity();
                $globalAttachment->model = $documentAttachment->model;
                $globalAttachment->foreign_id = $documentAttachment->document_id;
                $globalAttachment->filename = $documentAttachment->original;
                $globalAttachment->filesize = $documentAttachment->filesize;

                if ($AttachmentsTable->save($globalAttachment)) {
                    copy($filePath, $targetFolder . $globalAttachment->filename);
                }
            }
        }

        return 1;
    }
}
