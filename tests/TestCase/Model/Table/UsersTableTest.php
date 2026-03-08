<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\UsersTable;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\UsersTable Test Case
 */
class UsersTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\UsersTable
     */
    protected $Users;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Users',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Users') ? [] : ['className' => UsersTable::class];
        $this->Users = $this->getTableLocator()->get('Users', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Users);
        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @uses \App\Model\Table\UsersTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        // Valid entity produces no errors
        $user = $this->Users->newEntity([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertEmpty($user->getErrors(), 'Valid entity should have no errors');

        // name empty string fails (notEmptyString)
        $user = $this->Users->newEntity([
            'name' => '',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertArrayHasKey('name', $user->getErrors());

        // name exceeds maxLength(50)
        $user = $this->Users->newEntity([
            'name' => str_repeat('a', 51),
            'username' => 'testuser',
            'email' => 'test@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertArrayHasKey('name', $user->getErrors());
        $this->assertArrayHasKey('maxLength', $user->getErrors()['name']);

        // username empty string fails (notEmptyString)
        $user = $this->Users->newEntity([
            'name' => 'Test User',
            'username' => '',
            'email' => 'test@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertArrayHasKey('username', $user->getErrors());

        // username exceeds maxLength(100)
        $user = $this->Users->newEntity([
            'name' => 'Test User',
            'username' => str_repeat('u', 101),
            'email' => 'test@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertArrayHasKey('username', $user->getErrors());
        $this->assertArrayHasKey('maxLength', $user->getErrors()['username']);

        // invalid email
        $user = $this->Users->newEntity([
            'name' => 'Test',
            'username' => 'tester',
            'email' => 'not-an-email',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertArrayHasKey('email', $user->getErrors());

        // email empty string fails (notEmptyString)
        $user = $this->Users->newEntity([
            'name' => 'Test',
            'username' => 'tester',
            'email' => '',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertArrayHasKey('email', $user->getErrors());

        // privileges is required on create
        $user = $this->Users->newEntity([
            'name' => 'Test',
            'username' => 'tester',
            'email' => 'test@example.com',
            'active' => 1,
        ]);
        $this->assertArrayHasKey('privileges', $user->getErrors());
    }

    /**
     * Test validationResetPassword method
     *
     * @return void
     * @uses \App\Model\Table\UsersTable::validationResetPassword()
     */
    public function testValidationResetPassword(): void
    {
        // passwords must match
        $user = $this->Users->newEntity(
            ['passwd' => 'abcd', 'repeat_passwd' => 'abcd'],
            ['validate' => 'resetPassword'],
        );
        $this->assertArrayNotHasKey('passwd', $user->getErrors());
        $this->assertArrayNotHasKey('repeat_passwd', $user->getErrors());

        // passwd too short (minLength 4)
        $user = $this->Users->newEntity(
            ['passwd' => 'abc', 'repeat_passwd' => 'abc'],
            ['validate' => 'resetPassword'],
        );
        $this->assertArrayHasKey('passwd', $user->getErrors());

        // passwords do not match
        $user = $this->Users->newEntity(
            ['passwd' => 'abcd1234', 'repeat_passwd' => 'abcd0000'],
            ['validate' => 'resetPassword'],
        );
        $this->assertArrayHasKey('repeat_passwd', $user->getErrors());
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @uses \App\Model\Table\UsersTable::buildRules()
     */
    public function testBuildRules(): void
    {
        // Duplicate username should fail
        $user = $this->Users->newEntity([
            'name' => 'Duplicate',
            'username' => 'admin', // already in fixture
            'email' => 'unique@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertFalse($this->Users->save($user), 'Duplicate username should fail');
        $this->assertArrayHasKey('username', $user->getErrors());

        // Duplicate email should fail
        $user = $this->Users->newEntity([
            'name' => 'Duplicate',
            'username' => 'uniqueuser',
            'email' => 'admin@arhim.si', // already in fixture
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertFalse($this->Users->save($user), 'Duplicate email should fail');
        $this->assertArrayHasKey('email', $user->getErrors());

        // Valid unique user saves successfully
        $user = $this->Users->newEntity([
            'name' => 'Unique User',
            'username' => 'uniqueuser',
            'email' => 'unique@example.com',
            'privileges' => 10,
            'active' => 1,
        ]);
        $this->assertNotFalse($this->Users->save($user), 'Unique user should save');
    }

    /**
     * Test fetchForCompany method
     *
     * @return void
     * @uses \App\Model\Table\UsersTable::fetchForCompany()
     */
    public function testFetchForCompany(): void
    {
        // Both fixture users belong to COMPANY_FIRST and are active
        $result = $this->Users->fetchForCompany(COMPANY_FIRST);
        $this->assertCount(2, $result, 'Both active users from company should be returned');
        $this->assertArrayHasKey(USER_ADMIN, $result);
        $this->assertArrayHasKey(USER_COMMON, $result);

        // Unknown company returns empty
        $result = $this->Users->fetchForCompany('00000000-0000-0000-0000-000000000000');
        $this->assertEmpty($result, 'Unknown company should return no users');

        // inactive=true returns all users regardless of active flag
        $result = $this->Users->fetchForCompany(COMPANY_FIRST, ['inactive' => true]);
        $this->assertCount(2, $result, 'With inactive=true, all users should be returned');
    }

    /**
     * Test filter method
     *
     * @return void
     * @uses \App\Model\Table\UsersTable::filter()
     */
    public function testFilter(): void
    {
        $query = $this->Users->find();
        $request = new ServerRequest(['url' => '/users?active=1']);
        $ret = $this->Users->filter($query, $request);

        $this->assertIsArray($ret, 'filter() returns an array');
        $results = $query->all()->toArray();
        foreach ($results as $user) {
            $this->assertTrue((bool)$user->active, 'Filtered users should all be active');
        }
    }
}
