<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\User;
use App\Policy\UserPolicy;
use Cake\TestSuite\TestCase;

/**
 * App\Policy\UserPolicy Test Case
 *
 * canView       → same company_id
 * canProperties → same user id
 * canEdit       → same company_id AND hasRole('admin')  (privileges ≤ 5)
 * canDelete     → same company_id AND hasRole('admin')
 */
class UserPolicyTest extends TestCase
{
    protected UserPolicy $policy;

    private const COMPANY_A = '8155426d-2302-4fa5-97de-e33cefb9d704';
    private const COMPANY_B = '9999999d-2302-4fa5-97de-e33cefb9d704';

    public function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy();
    }

    private function makeUser(string $id, string $companyId, int $privileges): User
    {
        return new User([
            'id' => $id,
            'company_id' => $companyId,
            'privileges' => $privileges,
        ]);
    }

    // -------------------------------------------------------------------------
    // canView
    // -------------------------------------------------------------------------

    public function testCanViewSameCompany(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 2);
        $target = $this->makeUser('bbbb', self::COMPANY_A, 10);
        $this->assertTrue($this->policy->canView($authUser, $target));
    }

    public function testCannotViewDifferentCompany(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 2);
        $target = $this->makeUser('bbbb', self::COMPANY_B, 10);
        $this->assertFalse($this->policy->canView($authUser, $target));
    }

    // -------------------------------------------------------------------------
    // canProperties
    // -------------------------------------------------------------------------

    public function testCanPropertiesOwnAccount(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 10);
        $target = $this->makeUser('aaaa', self::COMPANY_A, 10);
        $this->assertTrue($this->policy->canProperties($authUser, $target));
    }

    public function testCannotPropertiesOtherAccount(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 10);
        $target = $this->makeUser('bbbb', self::COMPANY_A, 10);
        $this->assertFalse($this->policy->canProperties($authUser, $target));
    }

    // -------------------------------------------------------------------------
    // canEdit
    // -------------------------------------------------------------------------

    public function testCanEditSameCompanyAdmin(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 2); // admin
        $target = $this->makeUser('bbbb', self::COMPANY_A, 10);
        $this->assertTrue($this->policy->canEdit($authUser, $target));
    }

    public function testCannotEditSameCompanyNonAdmin(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 10); // editor only
        $target = $this->makeUser('bbbb', self::COMPANY_A, 10);
        $this->assertFalse($this->policy->canEdit($authUser, $target));
    }

    public function testCannotEditDifferentCompanyAdmin(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 2); // admin
        $target = $this->makeUser('bbbb', self::COMPANY_B, 10);
        $this->assertFalse($this->policy->canEdit($authUser, $target));
    }

    // -------------------------------------------------------------------------
    // canDelete
    // -------------------------------------------------------------------------

    public function testCanDeleteSameCompanyAdmin(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 2);
        $target = $this->makeUser('bbbb', self::COMPANY_A, 10);
        $this->assertTrue($this->policy->canDelete($authUser, $target));
    }

    public function testCannotDeleteSameCompanyNonAdmin(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 10);
        $target = $this->makeUser('bbbb', self::COMPANY_A, 10);
        $this->assertFalse($this->policy->canDelete($authUser, $target));
    }

    public function testCannotDeleteDifferentCompany(): void
    {
        $authUser = $this->makeUser('aaaa', self::COMPANY_A, 2);
        $target = $this->makeUser('bbbb', self::COMPANY_B, 10);
        $this->assertFalse($this->policy->canDelete($authUser, $target));
    }
}
