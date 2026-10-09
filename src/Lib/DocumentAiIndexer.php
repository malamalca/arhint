<?php
declare(strict_types=1);

namespace App\Lib;

use Cake\Datasource\EntityInterface;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Queue\QueueManager;
use DateTimeInterface;
use Throwable;

/**
 * Queues AI analysis of documents and their attachments, so they get an event in the vector database.
 *
 * The queue job (AiProcessLogJob) reads the text of the attachments, analyses it with AI and stores
 * the result in the vector database.
 */
class DocumentAiIndexer
{
    /**
     * Queue analysis of a document.
     *
     * @param \Cake\Datasource\EntityInterface $document The saved document.
     * @param array<int, string> $attachmentIds Ids of the attachments whose text is analysed.
     * @param bool $replace Replace the previous analysis of the document in the vector database.
     * @return bool False when the document cannot be analysed or the job could not be queued.
     */
    public function queue(EntityInterface $document, array $attachmentIds, bool $replace = false): bool
    {
        $userId = (string)$document->get('user_id');
        $documentId = (string)$document->get('id');
        if ($userId === '' || $documentId === '') {
            return false;
        }

        $dat = $document->get('dat_issue');
        $text = array_filter([
            'No: ' . $document->get('no'),
            'Title: ' . $document->get('title'),
            'Location: ' . $document->get('location'),
            'Issued: ' . ($dat instanceof DateTimeInterface ? $dat->format('Y-m-d') : (string)$dat),
            'Description: ' . strip_tags((string)$document->get('descript')),
        ], fn(string $line): bool => !str_ends_with($line, ': '));

        // Never let a queue problem break the caller.
        try {
            QueueManager::push('AiProcessLog', [
                'user_id' => $userId,
                'entity' => [
                    'id' => $documentId,
                    'model' => 'Document',
                    'foreign_id' => $documentId,
                    'project_id' => (string)$document->get('project_id'),
                    'user_id' => $userId,
                    'action' => 'document_created',
                    'descript' => implode("\n", $text),
                    'attachment_ids' => array_values($attachmentIds),
                    'replace' => $replace,
                ],
                'job_id' => $documentId,
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not queue AI analysis of a document: ' . $e->getMessage(), [
                'scope' => ['ai'],
                'document_id' => $documentId,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Queue a new analysis of a document that replaces the previous one.
     *
     * All attachments of the document are analysed together with its data.
     *
     * @param string $documentId Document id.
     * @return bool False when the document does not exist or the job could not be queued.
     */
    public function requeue(string $documentId): bool
    {
        $document = TableRegistry::getTableLocator()->get('Documents.Documents')
            ->find()
            ->where(['Documents.id' => $documentId])
            ->first();
        if ($document === null) {
            return false;
        }

        $attachmentIds = TableRegistry::getTableLocator()->get('Attachments')
            ->find('forModel', model: 'Document', foreignId: $documentId)
            ->orderBy(['Attachments.created' => 'ASC'])
            ->all()
            ->extract('id')
            ->toList();

        return $this->queue($document, $attachmentIds, true);
    }
}
