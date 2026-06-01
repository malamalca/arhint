<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * Drop the documents_logs table - logs are now stored in App.Logs (logs table)
 * with model='Documents.Document', 'Documents.Invoice', or 'Documents.TravelOrder',
 * foreign_id=document_id.
 */
class DropDocumentsLogs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        $table = $this->table('documents_logs');
        if ($table->exists()) {
            $table->drop()->save();
        }
    }
}
