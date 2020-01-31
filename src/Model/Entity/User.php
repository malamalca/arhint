<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\IdentityInterface;
use Cake\Auth\DefaultPasswordHasher;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property string $id
 * @property string|null $company_id
 * @property string|null $name
 * @property string|null $username
 * @property string|null $passwd
 * @property string|null $email
 * @property string|null $reset_key
 * @property int $privileges
 * @property bool $active
 * @property \Cake\I18n\FrozenTime|null $created
 * @property \Cake\I18n\FrozenTime|null $modified
 *
 * @property \App\Model\Entity\Company $company
 */
class User extends Entity implements IdentityInterface
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'company_id' => true,
        'name' => true,
        'username' => true,
        'passwd' => true,
        'email' => true,
        'reset_key' => true,
        'privileges' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'company' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array
     */
    protected $_hidden = [
        'passwd',
    ];

    /**
     * Entity to string magic method
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Set password method.
     *
     * @param string $password Users password.
     * @return string|bool
     */
    protected function _setPasswd($password)
    {
        return (new DefaultPasswordHasher())->hash($password);
    }

    /**
     * Authentication\IdentityInterface method
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->id;
    }

    /**
     * Authentication\IdentityInterface method
     *
     * @return \App\Model\Entity\User
     */
    public function getOriginalData()
    {
        return $this;
    }

    /**
     * Checks user's role.
     *
     * @param string $role User role.
     * @return bool
     */
    public function hasRole($role)
    {
        if ($role == 'root') {
            return $this->privileges <= 2;
        }
        if ($role == 'admin') {
            return $this->privileges <= 5;
        }
        if ($role == 'editor') {
            return $this->privileges <= 10;
        }

        return false;
    }

    /**
     * Returns users plugins
     *
     * @return array
     */
    public function getPlugins()
    {
        return ['LilCrm'];
    }
}
