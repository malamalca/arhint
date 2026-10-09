<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAiProcessedToAttachments extends BaseMigration
{
    /**
     * Time when the text of the attachment was analysed by AI and stored in the vector database.
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('attachments')
            ->addColumn('ai_processed', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
                'after' => 'description',
            ])
            ->update();
    }

    /**
     * @return void
     */
    public function down(): void
    {
        $this->table('attachments')
            ->removeColumn('ai_processed')
            ->update();
    }
}
