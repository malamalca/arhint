<?php
declare(strict_types=1);

namespace Documents\Event;

use App\Lib\AITool;
use App\Mailer\ArhintMailer;
use ArrayObject;
use Cake\Database\Expression\QueryExpression;
use Cake\Event\Event;
use Cake\Event\EventListenerInterface;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Documents\Lib\DocumentsExport;
use Documents\Lib\InvoicesExport;
use Documents\Lib\TravelOrdersExport;
use Documents\Model\Entity\TravelOrder;

class DocumentsAIToolsEvents implements EventListenerInterface
{
    /**
     * Return implemented events.
     *
     * @return array<string, mixed>
     */
    public function implementedEvents(): array
    {
        return [
            'App.AIAssistant.tools' => 'aiAssistantTools',
            'App.AIAssistant.executeTool' => 'aiAssistantExecuteTool',
        ];
    }

    /**
     * Add AI assistant tools.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param \ArrayObject $toolsList List of tools.
     * @return void
     */
    public function aiAssistantTools(Event $event, ArrayObject $toolsList): void
    {
        $toolsList->append(new AITool(
            name: 'Documents.get_document_counters',
            arguments: [
                'kind' => [
                    'type' => 'string',
                    'description' => 'Filter by document kind: "Documents", "Invoices", or "TravelOrders". '
                        . 'Omit to return all.',
                ],
            ],
            description: 'Lists available document counters (number sequences) grouped by kind and direction. '
                . 'Always call this first to obtain valid counter_id values before creating any document.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.search_invoices',
            arguments: [
                'counter_id' => [
                    'type' => 'string',
                    'description' => 'Filter by counter UUID.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Free-text search across invoice number, title, and buyer name.',
                ],
                'start' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format (dat_issue >=).',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format (dat_issue <=).',
                ],
                'month' => [
                    'type' => 'string',
                    'description' => 'Month filter in YYYY-MM format. Overrides start/end.',
                ],
                'expired' => [
                    'type' => 'string',
                    'description' => 'Return invoices with dat_expire on or before this YYYY-MM-DD date '
                        . '(overdue invoices).',
                ],
            ],
            description: 'Lists invoices filtered by counter, date range, free-text search, or overdue status. '
                . 'Returns id, no, title, dat_issue, dat_expire, net_total, total, and buyer name.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.get_invoice',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the invoice to retrieve.'],
            ],
            description: 'Fetches full details of a single invoice including issuer, buyer, receiver parties, '
                . 'all line items with quantities/prices/VAT, tax aggregation, payment details, and totals.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.create_invoice',
            arguments: [
                'counter_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the counter to use. Required. '
                        . 'Use Documents.get_document_counters to find a valid value.',
                ],
                'title' => ['type' => 'string', 'description' => 'Invoice title or subject. Required.'],
                'dat_issue' => [
                    'type' => 'string',
                    'description' => 'Issue date in YYYY-MM-DD format. Defaults to today.',
                ],
                'dat_service' => [
                    'type' => 'string',
                    'description' => 'Service/delivery date in YYYY-MM-DD format.',
                ],
                'dat_expire' => [
                    'type' => 'string',
                    'description' => 'Payment due date in YYYY-MM-DD format.',
                ],
                'pmt_type' => [
                    'type' => 'string',
                    'description' => 'Payment type code (e.g. "TRN" for bank transfer).',
                ],
                'pmt_ref' => [
                    'type' => 'string',
                    'description' => 'Payment reference number.',
                ],
                'descript' => ['type' => 'string', 'description' => 'Internal notes or description.'],
            ],
            description: 'Creates a new invoice draft. Auto-increments the counter and generates the document '
                . 'number via the counter mask. Returns the new invoice id and number.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.add_invoice_item',
            arguments: [
                'invoice_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the invoice. Required.',
                ],
                'descript' => [
                    'type' => 'string',
                    'description' => 'Item description. Required.',
                ],
                'qty' => [
                    'type' => 'number',
                    'description' => 'Quantity. Required.',
                ],
                'unit' => [
                    'type' => 'string',
                    'description' => 'Unit of measure (e.g. "pcs", "h"). Required.',
                ],
                'price' => [
                    'type' => 'number',
                    'description' => 'Unit price (net). Required.',
                ],
                'discount' => [
                    'type' => 'number',
                    'description' => 'Discount percentage (0–100). Defaults to 0.',
                ],
                'vat_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the VAT rate to apply. Required.',
                ],
            ],
            description: 'Appends a line item to an invoice. Invoice totals are recalculated automatically '
                . 'after save. Returns the new item id and computed net_total.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.update_invoice_item',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the invoice item. Required.'],
                'descript' => ['type' => 'string', 'description' => 'Item description.'],
                'qty' => ['type' => 'number', 'description' => 'Quantity.'],
                'unit' => ['type' => 'string', 'description' => 'Unit of measure.'],
                'price' => ['type' => 'number', 'description' => 'Unit price (net).'],
                'discount' => ['type' => 'number', 'description' => 'Discount percentage (0–100).'],
                'vat_id' => ['type' => 'string', 'description' => 'UUID of the VAT rate.'],
            ],
            description: 'Updates an existing invoice line item. Invoice totals are recalculated automatically.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.delete_invoice_item',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the invoice item to delete. Required.'],
            ],
            description: 'Removes a line item from an invoice. Invoice totals are recalculated automatically.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.get_invoice_report',
            arguments: [
                'counter_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the counter to report on. Required.',
                ],
                'month' => [
                    'type' => 'string',
                    'description' => 'Report for a single month in YYYY-MM format.',
                ],
                'start' => [
                    'type' => 'string',
                    'description' => 'Report start date in YYYY-MM-DD format. Used with end.',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'Report end date in YYYY-MM-DD format. Used with start.',
                ],
            ],
            description: 'Returns a financial summary for invoices in the given counter and date range: '
                . 'count, total net amount, and total gross amount. Useful for "how much did we invoice?" queries.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.search_documents',
            arguments: [
                'counter_id' => [
                    'type' => 'string',
                    'description' => 'Filter by counter UUID.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Free-text search across document number, title, location, and party name.',
                ],
                'start' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format (dat_issue >=).',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format (dat_issue <=).',
                ],
                'month' => [
                    'type' => 'string',
                    'description' => 'Month filter in YYYY-MM format. Overrides start/end.',
                ],
                'contact_id' => [
                    'type' => 'string',
                    'description' => 'Filter by CRM contact UUID.',
                ],
            ],
            description: 'Lists generic documents filtered by counter, date range, free-text, or contact. '
                . 'Returns id, no, title, dat_issue, and party names.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.get_document',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the document to retrieve.'],
            ],
            description: 'Fetches full details of a single generic document including issuer and receiver '
                . 'party data, linked documents, and attachments count.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.search_travel_orders',
            arguments: [
                'counter_id' => [
                    'type' => 'string',
                    'description' => 'Filter by counter UUID.',
                ],
                'search' => [
                    'type' => 'string',
                    'description' => 'Free-text search across number, title, location, description, and taskee.',
                ],
                'status' => [
                    'type' => 'string',
                    'description' => 'Filter by status. Accepted values: draft, waiting_approval, declined, '
                        . 'approved, waiting_processing, completed, open (all non-terminal), closed.',
                ],
                'employee_id' => [
                    'type' => 'string',
                    'description' => 'Filter by employee user UUID.',
                ],
                'start' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format (dat_task >=).',
                ],
                'end' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format (dat_task <=).',
                ],
            ],
            description: 'Lists travel orders filtered by counter, status, employee, date range, or text. '
                . 'Returns id, no, title, status, employee name, dat_task, and total.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.get_travel_order',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the travel order to retrieve.'],
            ],
            description: 'Fetches full details of a single travel order including employee, payer, '
                . 'mileage entries, expense entries, approval chain, and computed totals.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.create_travel_order',
            arguments: [
                'counter_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the counter to use. Required. '
                        . 'Use Documents.get_document_counters to find a valid value.',
                ],
                'title' => ['type' => 'string', 'description' => 'Brief title for the travel order. Required.'],
                'employee_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the traveling employee. Defaults to current user.',
                ],
                'dat_issue' => [
                    'type' => 'string',
                    'description' => 'Issue date in YYYY-MM-DD format. Defaults to today.',
                ],
                'dat_task' => [
                    'type' => 'string',
                    'description' => 'Travel/task date in YYYY-MM-DD format. Required.',
                ],
                'location' => ['type' => 'string', 'description' => 'Destination or travel location.'],
                'taskee' => ['type' => 'string', 'description' => 'Purpose of travel / task description.'],
                'descript' => ['type' => 'string', 'description' => 'Additional notes.'],
            ],
            description: 'Creates a new travel order in draft status. Auto-increments the counter and generates '
                . 'the document number. Returns the new travel order id and number.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.add_travel_expense',
            arguments: [
                'travel_order_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the parent travel order. Required.',
                ],
                'type' => [
                    'type' => 'string',
                    'description' => 'Expense category (e.g. "accommodation", "fuel", "meal"). Required.',
                ],
                'quantity' => [
                    'type' => 'number',
                    'description' => 'Quantity or count. Required.',
                ],
                'price' => [
                    'type' => 'number',
                    'description' => 'Unit price. Required.',
                ],
                'currency' => [
                    'type' => 'string',
                    'description' => 'ISO currency code (e.g. "EUR"). Required.',
                ],
                'description' => ['type' => 'string', 'description' => 'Expense description.'],
            ],
            description: 'Appends an expense entry to a travel order. The parent travel order total '
                . 'is recalculated automatically after save.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.send_document_email',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the invoice or document to send. Required.'],
                'kind' => [
                    'type' => 'string',
                    'description' => 'Document type: "invoice", "document", or "travel_order". Required.',
                ],
                'to' => ['type' => 'string', 'description' => 'Recipient email address. Required.'],
                'cc' => ['type' => 'string', 'description' => 'CC email address. Optional.'],
                'subject' => ['type' => 'string', 'description' => 'Email subject. Required.'],
                'body' => ['type' => 'string', 'description' => 'Plain-text email body. Optional.'],
                'include_attachments' => [
                    'type' => 'boolean',
                    'description' => 'Whether to attach file attachments linked to the document. Defaults to true.',
                ],
            ],
            description: 'Generates a PDF of the specified invoice or document and sends it to the given '
                . 'recipient by email. File attachments linked to the document are included by default.',
        ));

        $toolsList->append(new AITool(
            name: 'Documents.submit_travel_order',
            arguments: [
                'id' => ['type' => 'string', 'description' => 'UUID of the travel order. Required.'],
                'action' => [
                    'type' => 'string',
                    'description' => 'Transition to perform. Accepted values: '
                        . '"sign" (draft → waiting_approval), '
                        . '"approve" (waiting_approval → approved, admin only), '
                        . '"submit" (approved → waiting_processing), '
                        . '"process" (waiting_processing → completed, admin only).',
                ],
            ],
            description: 'Advances a travel order through its approval workflow. Enforces status machine '
                . 'rules and authorization. Returns the updated status.',
        ));
    }

    /**
     * Execute AI assistant tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param string $tool Tool name.
     * @param array<mixed> $arguments Tool arguments.
     * @return void
     */
    public function aiAssistantExecuteTool(Event $event, string $tool, array $arguments): void
    {
        $currentUser = $event->getData()[2] ?? null;

        match ($tool) {
            'Documents.get_document_counters' => $this->executeGetDocumentCounters(
                $event,
                $arguments,
                $currentUser,
            ),
            'Documents.search_invoices' => $this->executeSearchInvoices($event, $arguments, $currentUser),
            'Documents.get_invoice' => $this->executeGetInvoice($event, $arguments, $currentUser),
            'Documents.create_invoice' => $this->executeCreateInvoice($event, $arguments, $currentUser),
            'Documents.add_invoice_item' => $this->executeAddInvoiceItem($event, $arguments, $currentUser),
            'Documents.update_invoice_item' => $this->executeUpdateInvoiceItem($event, $arguments, $currentUser),
            'Documents.delete_invoice_item' => $this->executeDeleteInvoiceItem($event, $arguments, $currentUser),
            'Documents.get_invoice_report' => $this->executeGetInvoiceReport($event, $arguments, $currentUser),
            'Documents.search_documents' => $this->executeSearchDocuments($event, $arguments, $currentUser),
            'Documents.get_document' => $this->executeGetDocument($event, $arguments, $currentUser),
            'Documents.search_travel_orders' => $this->executeSearchTravelOrders(
                $event,
                $arguments,
                $currentUser,
            ),
            'Documents.get_travel_order' => $this->executeGetTravelOrder($event, $arguments, $currentUser),
            'Documents.create_travel_order' => $this->executeCreateTravelOrder(
                $event,
                $arguments,
                $currentUser,
            ),
            'Documents.add_travel_expense' => $this->executeAddTravelExpense($event, $arguments, $currentUser),
            'Documents.submit_travel_order' => $this->executeSubmitTravelOrder(
                $event,
                $arguments,
                $currentUser,
            ),
            'Documents.send_document_email' => $this->executeSendDocumentEmail(
                $event,
                $arguments,
                $currentUser,
            ),
            default => null,
        };
    }

    /**
     * Execute Documents.get_document_counters tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetDocumentCounters(Event $event, array $arguments, mixed $currentUser): void
    {
        $countersTable = TableRegistry::getTableLocator()->get('Documents.DocumentsCounters');

        $query = $currentUser->applyScope('index', $countersTable->find())
            ->select(['id', 'title', 'kind', 'direction', 'active', 'mask'])
            ->orderBy(['kind', 'direction', 'title']);

        if (!empty($arguments['kind'])) {
            $query->where(['DocumentsCounters.kind' => $arguments['kind']]);
        }

        $event->setResult($query->all()->toArray());
    }

    /**
     * Execute Documents.search_invoices tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSearchInvoices(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Documents\Model\Table\InvoicesTable $invoicesTable */
        $invoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        $filter = array_intersect_key($arguments, array_flip(['counter_id', 'search', 'start', 'end', 'month']));
        if (!empty($arguments['counter_id'])) {
            $filter['counter'] = $arguments['counter_id'];
        }
        if (!empty($arguments['expired'])) {
            $filter['expired'] = $arguments['expired'];
        }
        $params = $invoicesTable->filter($filter);

        $invoices = $currentUser->applyScope('index', $invoicesTable->find())
            ->select([
                'Invoices.id',
                'Invoices.no',
                'Invoices.title',
                'Invoices.dat_issue',
                'Invoices.dat_expire',
                'Invoices.net_total',
                'Invoices.total',
            ])
            ->contain(['Buyers'])
            ->where($params['conditions'])
            ->orderBy(['Invoices.dat_issue' => 'DESC', 'Invoices.counter' => 'DESC'])
            ->limit(20)
            ->all()
            ->toArray();

        $event->setResult($invoices);
    }

    /**
     * Execute Documents.get_invoice tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetInvoice(Event $event, array $arguments, mixed $currentUser): void
    {
        $invoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        $invoice = $invoicesTable->find()
            ->contain(['Issuers', 'Buyers', 'Receivers', 'InvoicesItems', 'InvoicesTaxes', 'DocumentsCounters'])
            ->where(['Invoices.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$invoice) {
            $event->setResult(['error' => 'Invoice not found.']);

            return;
        }

        if (!$currentUser->can('view', $invoice)) {
            $event->setResult(['error' => 'Access denied.']);

            return;
        }

        $event->setResult($invoice);
    }

    /**
     * Execute Documents.create_invoice tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeCreateInvoice(Event $event, array $arguments, mixed $currentUser): void
    {
        $invoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        $data = array_intersect_key(
            $arguments,
            array_flip(['counter_id', 'title', 'dat_issue', 'dat_service', 'dat_expire', 'pmt_type', 'pmt_ref',
                'descript']),
        );
        $data['owner_id'] = $currentUser->get('company_id');
        $data['user_id'] = $currentUser->get('id');
        if (empty($data['dat_issue'])) {
            $data['dat_issue'] = Date::today();
        }

        $invoice = $invoicesTable->newEntity($data);

        if (!$currentUser->can('edit', $invoice)) {
            $event->setResult(['error' => 'You are not authorized to create invoices.']);

            return;
        }

        if (empty($invoice->counter_id)) {
            $event->setResult(['error' => 'counter_id is required. ' .
                'Use Documents.get_document_counters to find a valid value.']);

            return;
        }

        // @phpstan-ignore-next-line
        $invoice->getNextCounterNo();

        if (!$invoice->getErrors() && $invoicesTable->save($invoice)) {
            $event->setResult(['id' => $invoice->id, 'no' => $invoice->get('no'), 'title' => $invoice->get('title')]);
        } else {
            $event->setResult(['error' => 'Failed to create invoice.', 'errors' => $invoice->getErrors()]);
        }
    }

    /**
     * Execute Documents.add_invoice_item tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddInvoiceItem(Event $event, array $arguments, mixed $currentUser): void
    {
        $invoice = $this->loadAccessibleInvoice($currentUser, $arguments['invoice_id'] ?? '');
        if (!$invoice) {
            $event->setResult(['error' => 'Invoice not found or access denied.']);

            return;
        }

        if (!$currentUser->can('edit', $invoice)) {
            $event->setResult(['error' => 'You are not authorized to edit this invoice.']);

            return;
        }

        $itemsTable = TableRegistry::getTableLocator()->get('Documents.InvoicesItems');
        $item = $itemsTable->newEntity([
            'invoice_id' => $invoice->id,
            'descript' => $arguments['descript'] ?? '',
            'qty' => $arguments['qty'] ?? 1,
            'unit' => $arguments['unit'] ?? 'pcs',
            'price' => $arguments['price'] ?? 0,
            'discount' => $arguments['discount'] ?? 0,
            'vat_id' => $arguments['vat_id'] ?? null,
        ]);

        if (!$item->getErrors() && $itemsTable->save($item)) {
            $event->setResult([
                'id' => $item->id,
                'net_total' => $item->get('net_total'),
                'total' => $item->get('total'),
            ]);
        } else {
            $event->setResult(['error' => 'Failed to add invoice item.', 'errors' => $item->getErrors()]);
        }
    }

    /**
     * Execute Documents.update_invoice_item tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeUpdateInvoiceItem(Event $event, array $arguments, mixed $currentUser): void
    {
        $itemsTable = TableRegistry::getTableLocator()->get('Documents.InvoicesItems');

        $item = $itemsTable->find()
            ->contain(['Invoices'])
            ->where(['InvoicesItems.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$item) {
            $event->setResult(['error' => 'Invoice item not found.']);

            return;
        }

        if (!$currentUser->can('edit', $item->invoice)) {
            $event->setResult(['error' => 'You are not authorized to edit this invoice.']);

            return;
        }

        $updateData = array_intersect_key(
            $arguments,
            array_flip(['descript', 'qty', 'unit', 'price', 'discount', 'vat_id']),
        );
        // @phpstan-ignore argument.templateType
        $itemsTable->patchEntity($item, $updateData);

        // @phpstan-ignore argument.templateType
        if (!$item->getErrors() && $itemsTable->save($item)) {
            $event->setResult([
                'id' => $item->id,
                'net_total' => $item->get('net_total'),
                'total' => $item->get('total'),
            ]);
        } else {
            $event->setResult(['error' => 'Failed to update invoice item.', 'errors' => $item->getErrors()]);
        }
    }

    /**
     * Execute Documents.delete_invoice_item tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeDeleteInvoiceItem(Event $event, array $arguments, mixed $currentUser): void
    {
        $itemsTable = TableRegistry::getTableLocator()->get('Documents.InvoicesItems');

        $item = $itemsTable->find()
            ->contain(['Invoices'])
            ->where(['InvoicesItems.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$item) {
            $event->setResult(['error' => 'Invoice item not found.']);

            return;
        }

        if (!$currentUser->can('edit', $item->invoice)) {
            $event->setResult(['error' => 'You are not authorized to edit this invoice.']);

            return;
        }

        if ($itemsTable->delete($item)) {
            $event->setResult(['success' => true]);
        } else {
            $event->setResult(['error' => 'Failed to delete invoice item.']);
        }
    }

    /**
     * Execute Documents.get_invoice_report tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetInvoiceReport(Event $event, array $arguments, mixed $currentUser): void
    {
        if (empty($arguments['counter_id'])) {
            $event->setResult(['error' => 'counter_id is required.']);

            return;
        }

        /** @var \Documents\Model\Table\InvoicesTable $invoicesTable */
        $invoicesTable = TableRegistry::getTableLocator()->get('Documents.Invoices');

        $filter = ['counter' => $arguments['counter_id']];
        if (!empty($arguments['month'])) {
            $filter['month'] = $arguments['month'];
        } elseif (!empty($arguments['start'])) {
            $filter['start'] = $arguments['start'];
            if (!empty($arguments['end'])) {
                $filter['end'] = $arguments['end'];
            }
        }
        $params = $invoicesTable->filter($filter);

        $query = $currentUser->applyScope('index', $invoicesTable->find())
            ->select([
                'cnt' => $invoicesTable->find()->func()->count('*'),
                'sum_net' => $invoicesTable->find()->func()->sum('Invoices.net_total'),
                'sum_total' => $invoicesTable->find()->func()->sum('Invoices.total'),
            ])
            ->where($params['conditions'])
            ->disableHydration();

        $result = $query->first();

        $event->setResult([
            'counter_id' => $arguments['counter_id'],
            'count' => (int)($result['cnt'] ?? 0),
            'sum_net_total' => round((float)($result['sum_net'] ?? 0), 2),
            'sum_total' => round((float)($result['sum_total'] ?? 0), 2),
        ]);
    }

    /**
     * Execute Documents.search_documents tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSearchDocuments(Event $event, array $arguments, mixed $currentUser): void
    {
        $documentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        $filter = array_intersect_key($arguments, array_flip(['search', 'start', 'end', 'month', 'contact_id']));
        if (!empty($arguments['counter_id'])) {
            $filter['counter'] = $arguments['counter_id'];
        }
        // @phpstan-ignore-next-line
        $params = $documentsTable->filter($filter);

        $documents = $currentUser->applyScope('index', $documentsTable->find())
            ->select([
                'Documents.id',
                'Documents.no',
                'Documents.title',
                'Documents.dat_issue',
                'Documents.location',
            ])
            ->contain(['Issuers', 'Receivers'])
            ->where($params['conditions'])
            ->orderBy(['Documents.dat_issue' => 'DESC', 'Documents.counter' => 'DESC'])
            ->limit(20)
            ->all()
            ->toArray();

        $event->setResult($documents);
    }

    /**
     * Execute Documents.get_document tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetDocument(Event $event, array $arguments, mixed $currentUser): void
    {
        $documentsTable = TableRegistry::getTableLocator()->get('Documents.Documents');

        $document = $documentsTable->find()
            ->contain(['Issuers', 'Receivers', 'DocumentsCounters', 'DocumentsLinks'])
            ->where(['Documents.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$document) {
            $event->setResult(['error' => 'Document not found.']);

            return;
        }

        if (!$currentUser->can('view', $document)) {
            $event->setResult(['error' => 'Access denied.']);

            return;
        }

        $event->setResult($document);
    }

    /**
     * Execute Documents.search_travel_orders tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSearchTravelOrders(Event $event, array $arguments, mixed $currentUser): void
    {
        /** @var \Documents\Model\Table\TravelOrdersTable $travelOrdersTable */
        $travelOrdersTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');

        $filter = array_intersect_key($arguments, array_flip(['search', 'status', 'start', 'end', 'month']));
        if (!empty($arguments['counter_id'])) {
            $filter['counter'] = $arguments['counter_id'];
        }
        if (!empty($arguments['employee_id'])) {
            $filter['employee'] = $arguments['employee_id'];
        }
        $params = $travelOrdersTable->filter($filter);

        $travelOrders = $currentUser->applyScope('index', $travelOrdersTable->find())
            ->select([
                'TravelOrders.id',
                'TravelOrders.no',
                'TravelOrders.title',
                'TravelOrders.status',
                'TravelOrders.dat_task',
                'TravelOrders.total',
                'TravelOrders.employee_id',
            ])
            ->contain(['Employees'])
            ->where($params['conditions'])
            ->orderBy(['TravelOrders.dat_task' => 'DESC', 'TravelOrders.counter' => 'DESC'])
            ->limit(20)
            ->all()
            ->toArray();

        $event->setResult($travelOrders);
    }

    /**
     * Execute Documents.get_travel_order tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeGetTravelOrder(Event $event, array $arguments, mixed $currentUser): void
    {
        $travelOrdersTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');

        $travelOrder = $travelOrdersTable->find()
            ->contain(['Employees', 'Payers', 'TravelOrdersMileages', 'TravelOrdersExpenses',
                'EnteredBy', 'ApprovedBy', 'ProcessedBy', 'DocumentsCounters'])
            ->where(['TravelOrders.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$travelOrder) {
            $event->setResult(['error' => 'Travel order not found.']);

            return;
        }

        if (!$currentUser->can('view', $travelOrder)) {
            $event->setResult(['error' => 'Access denied.']);

            return;
        }

        $event->setResult($travelOrder);
    }

    /**
     * Execute Documents.create_travel_order tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeCreateTravelOrder(Event $event, array $arguments, mixed $currentUser): void
    {
        $travelOrdersTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');

        $data = array_intersect_key(
            $arguments,
            array_flip(['counter_id', 'title', 'employee_id', 'dat_issue', 'dat_task', 'location', 'taskee',
                'descript']),
        );
        $data['owner_id'] = $currentUser->get('company_id');
        $data['entered_by_id'] = $currentUser->get('id');
        $data['status'] = TravelOrder::STATUS_DRAFT;
        if (empty($data['employee_id'])) {
            $data['employee_id'] = $currentUser->get('id');
        }
        if (empty($data['dat_issue'])) {
            $data['dat_issue'] = Date::today();
        }

        $travelOrder = $travelOrdersTable->newEntity($data);

        if (!$currentUser->can('edit', $travelOrder)) {
            $event->setResult(['error' => 'You are not authorized to create travel orders.']);

            return;
        }

        if (empty($travelOrder->counter_id)) {
            $event->setResult(['error' => 'counter_id is required. Use Documents.get_document_counters to find a valid value.']);

            return;
        }

        // @phpstan-ignore-next-line
        $travelOrder->getNextCounterNo();

        if (!$travelOrder->getErrors() && $travelOrdersTable->save($travelOrder)) {
            $event->setResult([
                'id' => $travelOrder->id,
                'no' => $travelOrder->get('no'),
                'status' => $travelOrder->get('status'),
            ]);
        } else {
            $event->setResult([
                'error' => 'Failed to create travel order.',
                'errors' => $travelOrder->getErrors(),
            ]);
        }
    }

    /**
     * Execute Documents.add_travel_expense tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeAddTravelExpense(Event $event, array $arguments, mixed $currentUser): void
    {
        $travelOrder = $this->loadAccessibleTravelOrder($currentUser, $arguments['travel_order_id'] ?? '');
        if (!$travelOrder) {
            $event->setResult(['error' => 'Travel order not found or access denied.']);

            return;
        }

        if (!$currentUser->can('edit', $travelOrder)) {
            $event->setResult(['error' => 'You are not authorized to edit this travel order.']);

            return;
        }

        $expensesTable = TableRegistry::getTableLocator()->get('Documents.TravelOrdersExpenses');
        $expense = $expensesTable->newEntity([
            'travel_order_id' => $travelOrder->id,
            'type' => $arguments['type'] ?? '',
            'quantity' => $arguments['quantity'] ?? 1,
            'price' => $arguments['price'] ?? 0,
            'currency' => $arguments['currency'] ?? 'EUR',
            'description' => $arguments['description'] ?? null,
        ]);

        if (!$expense->getErrors() && $expensesTable->save($expense)) {
            $event->setResult(['id' => $expense->id]);
        } else {
            $event->setResult(['error' => 'Failed to add expense.', 'errors' => $expense->getErrors()]);
        }
    }

    /**
     * Execute Documents.submit_travel_order tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSubmitTravelOrder(Event $event, array $arguments, mixed $currentUser): void
    {
        $travelOrdersTable = TableRegistry::getTableLocator()->get('Documents.TravelOrders');

        $travelOrder = $travelOrdersTable->find()
            ->where(['TravelOrders.id' => $arguments['id'] ?? ''])
            ->first();

        if (!$travelOrder) {
            $event->setResult(['error' => 'Travel order not found.']);

            return;
        }

        if (!$currentUser->can('view', $travelOrder)) {
            $event->setResult(['error' => 'Access denied.']);

            return;
        }

        $action = $arguments['action'] ?? '';
        $now = new DateTime();

        switch ($action) {
            case 'sign':
                if ($travelOrder->status !== TravelOrder::STATUS_DRAFT) {
                    $event->setResult(['error' => 'Travel order must be in draft status to sign.']);

                    return;
                }
                if (!$currentUser->can('sign', $travelOrder)) {
                    $event->setResult(['error' => 'You are not authorized to sign this travel order.']);

                    return;
                }
                $travelOrder->status = TravelOrder::STATUS_WAITING_APPROVAL;
                $travelOrder->entered_at = $now;
                break;

            case 'approve':
                if ($travelOrder->status !== TravelOrder::STATUS_WAITING_APPROVAL) {
                    $event->setResult(['error' => 'Travel order must be waiting approval to approve.']);

                    return;
                }
                if (!$currentUser->can('approve', $travelOrder)) {
                    $event->setResult(['error' => 'You are not authorized to approve travel orders.']);

                    return;
                }
                $travelOrder->status = TravelOrder::STATUS_APPROVED;
                $travelOrder->approved_by_id = $currentUser->get('id');
                $travelOrder->approved_at = $now;
                break;

            case 'submit':
                if ($travelOrder->status !== TravelOrder::STATUS_APPROVED) {
                    $event->setResult(['error' => 'Travel order must be approved before submitting.']);

                    return;
                }
                if (!$currentUser->can('submit', $travelOrder)) {
                    $event->setResult(['error' => 'You are not authorized to submit this travel order.']);

                    return;
                }
                $travelOrder->status = TravelOrder::STATUS_WAITING_PROCESSING;
                break;

            case 'process':
                if ($travelOrder->status !== TravelOrder::STATUS_WAITING_PROCESSING) {
                    $event->setResult(['error' => 'Travel order must be waiting processing to process.']);

                    return;
                }
                if (!$currentUser->can('approve', $travelOrder)) {
                    $event->setResult(['error' => 'You are not authorized to process travel orders.']);

                    return;
                }
                $travelOrder->status = TravelOrder::STATUS_COMPLETED;
                $travelOrder->processed_by_id = $currentUser->get('id');
                $travelOrder->processed_at = $now;
                break;

            default:
                $event->setResult([
                    'error' => 'Unknown action. Use: sign, approve, submit, or process.',
                ]);

                return;
        }

        // @phpstan-ignore argument.templateType
        if ($travelOrdersTable->save($travelOrder)) {
            $event->setResult(['id' => $travelOrder->id, 'status' => $travelOrder->get('status')]);
        } else {
            $event->setResult([
                'error' => 'Failed to update travel order.',
                'errors' => $travelOrder->getErrors(),
            ]);
        }
    }

    /**
     * Execute Documents.send_document_email tool.
     *
     * @param \Cake\Event\Event $event Event object.
     * @param array<mixed> $arguments Tool arguments.
     * @param mixed $currentUser Current user.
     * @return void
     */
    private function executeSendDocumentEmail(Event $event, array $arguments, mixed $currentUser): void
    {
        $id = $arguments['id'] ?? '';
        $kind = strtolower($arguments['kind'] ?? 'invoice');
        $to = $arguments['to'] ?? '';
        $subject = $arguments['subject'] ?? '';

        if (empty($id) || empty($to) || empty($subject)) {
            $event->setResult(['error' => 'id, to, and subject are required.']);

            return;
        }

        // Resolve model/table/exporter from kind
        [$tableName, $modelAlias, $exporter] = match ($kind) {
            'document' => ['Documents.Documents', 'Document', new DocumentsExport()],
            'travel_order' => ['Documents.TravelOrders', 'TravelOrder', new TravelOrdersExport()],
            default => ['Documents.Invoices', 'Invoice', new InvoicesExport()],
        };

        // Load and authorize the entity
        $table = TableRegistry::getTableLocator()->get($tableName);
        $entity = $currentUser->applyScope('index', $table->find())
            ->where([$table->getAlias() . '.id' => $id])
            ->first();

        if (!$entity) {
            $event->setResult(['error' => 'Document not found or access denied.']);

            return;
        }

        // Generate PDF
        $filter = ['id' => $id];
        $documents = $exporter->find($filter);
        $currentUser->applyScope('index', $documents);
        $documentList = $documents->toArray();

        if (empty($documentList)) {
            $event->setResult(['error' => 'Could not load document for export.']);

            return;
        }

        $pdfData = $exporter->export('pdf', $documentList);
        if (empty($pdfData)) {
            $event->setResult(['error' => 'Failed to generate PDF.']);

            return;
        }

        $email = new ArhintMailer(['user' => $currentUser]);
        $email
            ->setFrom([(string)$currentUser->email => $currentUser->name])
            ->setTo($to)
            ->setSubject($subject);

        $cc = $arguments['cc'] ?? null;
        if (!empty($cc)) {
            $email->addCc($cc);
        }

        // Build PDF attachment name
        $doc = $documentList[0];
        $attachmentName = (string)mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', (string)$doc->title);
        $attachmentName = (string)mb_ereg_replace("([\.]{2,})", '', $attachmentName);
        if (empty($attachmentName)) {
            $attachmentName = 'document';
        }

        $attachments = [
            $attachmentName . '.pdf' => [
                'data' => $pdfData,
                'mimetype' => 'application/pdf',
            ],
        ];

        // Optionally include file attachments
        $includeAttachments = $arguments['include_attachments'] ?? true;
        if ($includeAttachments) {
            $AttachmentsTable = TableRegistry::getTableLocator()->get('App.Attachments');
            $docAttachments = $AttachmentsTable->find()
                ->select(['id', 'model', 'filename'])
                ->where(function (QueryExpression $exp, SelectQuery $query) use ($modelAlias, $id) {
                    return $exp->and(['model' => $modelAlias, 'foreign_id' => $id]);
                })
                ->all();

            foreach ($docAttachments as $attachment) {
                $attachments[$attachment->filename] = [
                    'file' => $attachment->getFilePath(),
                ];
            }
        }

        $email->setAttachments($attachments);

        $result = $email->deliver((string)($arguments['body'] ?? ''));

        if ($result) {
            /** @var \App\Model\Table\LogsTable $LogsTable */
            $LogsTable = TableRegistry::getTableLocator()->get('App.Logs');
            $LogsTable::log(
                model: $modelAlias,
                foreignId: $id,
                userId: $currentUser->id,
                action: 'DocumentEmail',
                details: json_encode([
                    'to' => $to,
                    'cc' => $cc,
                    'subject' => $subject,
                ], JSON_THROW_ON_ERROR),
            );

            $event->setResult(['success' => true, 'to' => $to]);
        } else {
            $event->setResult(['error' => 'Failed to send email.']);
        }
    }

    /**
     * Load an invoice accessible to the current user.
     *
     * @param mixed $currentUser Current user.
     * @param string $invoiceId Invoice UUID.
     * @return \Documents\Model\Entity\Invoice|null
     */
    private function loadAccessibleInvoice(mixed $currentUser, string $invoiceId): mixed
    {
        if (empty($invoiceId)) {
            return null;
        }

        return $currentUser->applyScope('index', TableRegistry::getTableLocator()->get('Documents.Invoices')->find())
            ->where(['Invoices.id' => $invoiceId])
            ->first();
    }

    /**
     * Load a travel order accessible to the current user.
     *
     * @param mixed $currentUser Current user.
     * @param string $travelOrderId Travel order UUID.
     * @return \Documents\Model\Entity\TravelOrder|null
     */
    private function loadAccessibleTravelOrder(mixed $currentUser, string $travelOrderId): mixed
    {
        if (empty($travelOrderId)) {
            return null;
        }

        return $currentUser->applyScope(
            'index',
            TableRegistry::getTableLocator()->get('Documents.TravelOrders')->find(),
        )
            ->where(['TravelOrders.id' => $travelOrderId])
            ->first();
    }
}
