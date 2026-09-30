<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * ApiRequest Entity - remembered Idempotency-Key of a REST API request
 *
 * @property string $id
 * @property string $user_id
 * @property string $idempotency_key
 * @property string $request_hash
 * @property string|null $invoice_id
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class ApiRequest extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => false,
    ];
}
