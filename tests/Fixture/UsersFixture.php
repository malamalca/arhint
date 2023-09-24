<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\TestSuite\Fixture\TestFixture;

/**
 * UsersFixture
 */
class UsersFixture extends TestFixture
{
    public string $connection = 'test';

    public function init(): void
    {
        parent::init();

        $this->records = [
            [
                'id' => '048acacf-d87c-4088-a3a7-4bab30f6a040',
                'company_id' => COMPANY_FIRST,
                'name' => 'Admin User',
                'username' => 'admin',
                'passwd' => (new DefaultPasswordHasher())->hash('pass'),
                'email' => 'admin@arhim.si',
                'reset_key' => null,
                'privileges' => 2,
                'active' => 1,
                'created' => '2020-01-12 19:40:23',
                'modified' => '2020-01-12 19:40:23',
            ],
            [
                'id' => '048acacf-d87c-4088-a3a7-4bab30f6a041',
                'company_id' => COMPANY_FIRST,
                'name' => 'Regular User',
                'username' => 'user',
                'passwd' => (new DefaultPasswordHasher())->hash('password'),
                'email' => 'user@arhim.si',
                'reset_key' => null,
                'privileges' => 10,
                'active' => 1,
                'created' => '2020-01-12 19:40:23',
                'modified' => '2020-01-12 19:40:23',
            ],
        ];
    }
}
