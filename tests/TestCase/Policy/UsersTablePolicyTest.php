<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Policy\UsersTablePolicy;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Policy\UsersTablePolicy Test Case
 *
 * scopeIndex → WHERE Users.company_id = authUser->company_id
 */
class UsersTablePolicyTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $fixtures = ['app.Users'];

    /** Constant from UsersFixture */
    private const COMPANY_FIRST = '8155426d-2302-4fa5-97de-e33cefb9d704';
    private const USER_ADMIN = '048acacf-d87c-4088-a3a7-4bab30f6a040';

    protected UsersTablePolicy $policy;

    public function setUp(): void
    {
        parent::setUp();
        $this->policy = new UsersTablePolicy();
    }

    /**
     * scopeIndex adds a company_id WHERE clause and only returns users
     * from the same company as the logged-in user.
     */
    public function testScopeIndexFiltersCompany(): void
    {
        $Users = TableRegistry::getTableLocator()->get('Users');

        /** @var \App\Model\Entity\User $authUser */
        $authUser = $Users->get(self::USER_ADMIN);

        $query = $Users->find();
        $scoped = $this->policy->scopeIndex($authUser, $query);

        $results = $scoped->all();

        // Every returned user must belong to the same company
        foreach ($results as $user) {
            $this->assertEquals(
                self::COMPANY_FIRST,
                $user->company_id,
                "User {$user->id} has wrong company_id",
            );
        }

        // There must be at least one result (the auth user itself)
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * scopeIndex returns a SelectQuery (fluent / chainable).
     */
    public function testScopeIndexReturnsQuery(): void
    {
        $Users = TableRegistry::getTableLocator()->get('Users');
        /** @var \App\Model\Entity\User $authUser */
        $authUser = $Users->get(self::USER_ADMIN);

        $query = $Users->find();
        $result = $this->policy->scopeIndex($authUser, $query);

        $this->assertInstanceOf(SelectQuery::class, $result);
    }
}
