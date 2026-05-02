<?php
declare(strict_types=1);

namespace Expenses\Model\Entity;

use Cake\ORM\Entity;

/**
 * Account Entity.
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $code
 * @property string $name
 * @property int $lft
 * @property int $rght
 * @property int $level
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Expenses\Model\Entity\Account|null $parent_account
 * @property \Expenses\Model\Entity\Account[] $child_accounts
 */
class Account extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        '*' => true,
        'id' => false,
    ];

    /**
     * Magic method __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)($this->code . ' – ' . $this->name);
    }
}
