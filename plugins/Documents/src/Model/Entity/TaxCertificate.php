<?php
declare(strict_types=1);

namespace Documents\Model\Entity;

use Cake\ORM\Entity;

/**
 * TaxCertificate Entity
 *
 * @property string $id
 * @property string $user_id
 * @property string|null $tax_no
 * @property string|null $p12
 * @property string|null $password
 * @property \Cake\I18n\DateTime|null $valid_to
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 */
class TaxCertificate extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => false,
        'tax_no' => true,
    ];

    /**
     * @var array<string>
     */
    protected array $_hidden = ['p12', 'password'];
}
