<?php
declare(strict_types=1);

/**
 * LilDocument View helper.
 *
 */
namespace LilInvoices\View\Helper;

use Cake\Core\Configure;
use Cake\View\Helper;
use LilInvoices\Model\Entity\Invoice;

/**
 * LilDocument View helper.
 *
 */
class LilDocumentHelper extends Helper
{
    /**
     * Checks if passed document is Invoice
     *
     * @param \LilInvoices\Model\Entity\Invoice $document Document Entity.
     *
     * @return bool
     */
    public function isInvoice(Invoice $document)
    {
        return in_array($document->doc_type, Configure::read('LilInvoices.invoiceDocTypes'));
    }
}
