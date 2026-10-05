<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateDocumentsApiRequests extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // Idempotency-Key records of the REST API; `invoice_id` is empty while the request is in progress.
        $this->table('documents_api_requests', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid')
            ->addColumn('user_id', 'uuid')
            ->addColumn('idempotency_key', 'string', ['limit' => 255])
            ->addColumn('request_hash', 'char', ['limit' => 64])
            ->addColumn('invoice_id', 'uuid', ['null' => true])
            ->addColumn('created', 'datetime', ['null' => true])
            ->addColumn('modified', 'datetime', ['null' => true])
            ->addIndex(['user_id', 'idempotency_key'], ['unique' => true])
            ->create();
    }
}
