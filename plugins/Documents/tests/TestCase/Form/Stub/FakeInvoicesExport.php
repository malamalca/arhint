<?php
declare(strict_types=1);

namespace Documents\Test\TestCase\Form\Stub;

use Cake\ORM\Query\SelectQuery;
use Documents\Lib\InvoicesExport;

/**
 * Exporter stub: returns preset invoices and fake PDF / eSlog 2.0 output.
 */
class FakeInvoicesExport extends InvoicesExport
{
    /**
     * @var array<\Documents\Model\Entity\Invoice>
     */
    public static array $invoices = [];

    /**
     * @inheritDoc
     */
    public function find(array $filter): SelectQuery
    {
        $query = new class (self::$invoices) extends SelectQuery {
            /**
             * @param array<mixed> $items Items to return.
             */
            public function __construct(private array $items)
            {
            }

            /**
             * @inheritDoc
             */
            public function toArray(): array
            {
                return $this->items;
            }
        };

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function export(string $ext, array $invoices): mixed
    {
        return $ext === 'pdf' ? '%PDF-1.4 fake' : '<Invoice xmlns="urn:eslog:2.00"/>';
    }
}
