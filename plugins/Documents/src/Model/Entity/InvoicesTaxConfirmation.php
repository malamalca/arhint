<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * InvoicesTaxConfirmation Entity
 *
 * @property string $id
 * @property string $invoice_id
 * @property string|null $user_id
 * @property string|null $bp_no
 * @property string|null $device_no
 * @property string|null $issuer_taxno
 * @property string|null $operator_taxno
 * @property \Cake\I18n\DateTime $issued_at
 * @property string|null $zoi
 * @property string|null $qr
 * @property string|null $eor
 * @property string|null $error_code
 * @property string|null $error_message
 * @property string|null $last_request
 * @property string|null $last_response
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class InvoicesTaxConfirmation extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Invoice is confirmed by tax authority.
     *
     * @return bool
     */
    public function isConfirmed(): bool
    {
        return !empty($this->eor);
    }
}
