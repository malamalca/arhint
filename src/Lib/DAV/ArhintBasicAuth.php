<?php
declare(strict_types=1);

namespace App\Lib\DAV;

use Authentication\PasswordHasher\PasswordHasherFactory;
use Cake\ORM\TableRegistry;
use Cake\Utility\Security;
use Sabre\DAV\Auth\Backend\AbstractBasic;

/**
 * This is an authentication backend that uses a database to manage passwords.
 */
class ArhintBasicAuth extends AbstractBasic
{
    /**
     * Validates a username and password.
     *
     * This method should return true or false depending on if login
     * succeeded.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function validateUserPass($username, $password): bool
    {
        $Users = TableRegistry::getTableLocator()->get('Users');
        $user = $Users->find()
            ->select()
            ->where(['username' => $username])
            ->first();

        if (!$user) {
            return false;
        }

        $Hasher = PasswordHasherFactory::build([
            'className' => 'Authentication\PasswordHasher\DefaultPasswordHasher',
            'hashType' => PASSWORD_BCRYPT,
            'hashOptions' => ['salt' => Security::getSalt()],
        ]);

        return $Hasher->check($password, $user->passwd);
    }
}
