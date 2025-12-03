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
     * Execute modified
     *
     * @param \Cake\Console\Arguments $args Arguments
     * @param \Cake\Console\ConsoleIo $io Console io
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        //select * from documents_attachments where original in (select original from documents_attachments group by original having count(original) > 1) ORDER BY `filename` ASC

        $subquery = $this->fetchTable('Documents.DocumentsAttachments')->find();
        $expr = $subquery->newExpr()->add('count(original) > 1');

        $subquery
            ->select(['original'])
            ->groupBy(['original'])
            ->having($expr);

        $attachments = $this->fetchTable('Documents.DocumentsAttachments')->find()
            ->select()
            ->where(['original IN' => $subquery])
            ->all();

        $AttachmentsTable = $this->fetchTable('Attachments');

        // najprej zbiršem vse neveljavne fajle
        foreach ($attachments as $atch) {
            $targetFolder = Configure::read('App.uploadFolder') . $atch->model . DS;
            $filePath = $targetFolder . $atch->filename;

            if (file_exists($filePath)) {
                $io->out('Deleting ...' . $filePath);
                unlink($filePath);
            }
        }

        // potem filam še enkrat
        foreach ($attachments as $atch) {
            $globalAtch = $AttachmentsTable->find()
                ->select()
                ->where(['foreign_id' => $atch->document_id])
                ->first();

            if ($globalAtch) {
                $filePath = Configure::read('App.uploadFolder') . 'duplicates' . DS . $atch->filename;
                if (file_exists($filePath)) {
                    $io->out('Processing: ' . $filePath);

                    // check for existing file
                    $folderDest = Configure::read('App.uploadFolder') . $atch->model . DS;
                    $fileDest = $folderDest . $atch->original;

                    // if file exists, add or increment index _XX before extension until unique
                    if (file_exists($fileDest)) {
                        $ext = pathinfo($atch->original, PATHINFO_EXTENSION);
                        $name = pathinfo($atch->original, PATHINFO_FILENAME);

                        // detect existing trailing _NN (two digits or more)
                        if (preg_match('/^(.*)_([0-9]+)$/', $name, $m)) {
                            $base = $m[1];
                            $index = (int)$m[2];
                        } else {
                            $base = $name;
                            $index = 0;
                        }

                        do {
                            $index++;
                            $indexedName = sprintf('%s_%d', $base, $index);
                            $newFilename = $ext !== '' ? $indexedName . '.' . $ext : $indexedName;
                            $fileDest = $folderDest . $newFilename;
                        } while (file_exists($fileDest));

                        // update entity filename so DB / further code sees the new name
                        $io->out('Changing filename ...' . $globalAtch->filename . ' -> ' . $newFilename);
                        $globalAtch->filename = $newFilename;
                        $io->out('Saving..');
                        $AttachmentsTable->save($globalAtch);
                    }

                    $io->out('Copying ...' . $filePath . ' -> ' . $fileDest);
                    copy($filePath, $fileDest);
                    $io->out('Deleting ...' . $filePath);
                    unlink($filePath);
                } else {
                    $io->out('File does not exist: ' . $filePath);
                }
            }
        }

        return 1;
    }

    /**
     * Hourly heartbeat function
     *
     * @param \Cake\Console\Arguments $args Console arguments
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @return int
     */
    public function executeOld(Arguments $args, ConsoleIo $io): int
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
