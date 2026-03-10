<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\DashboardNote;
use App\Model\Entity\User;
use App\Policy\DashboardNotePolicy;
use Cake\TestSuite\TestCase;

/**
 * App\Policy\DashboardNotePolicy Test Case
 *
 * canEdit → ($authUser->id == $note->user_id) || $authUser->hasRole('admin')
 *
 * hasRole('admin') requires privileges ≤ 5.
 */
class DashboardNotePolicyTest extends TestCase
{
    protected DashboardNotePolicy $policy;

    public function setUp(): void
    {
        parent::setUp();
        $this->policy = new DashboardNotePolicy();
    }

    /** Build a lightweight User entity without hitting the DB. */
    private function makeUser(string $id, int $privileges): User
    {
        return new User(['id' => $id, 'privileges' => $privileges]);
    }

    /** Build a lightweight DashboardNote entity. */
    private function makeNote(string $userId): DashboardNote
    {
        return new DashboardNote([
            'id' => '00000000-0000-0000-0000-000000000001',
            'user_id' => $userId,
            'note' => 'Test note',
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * The note owner can always edit their own note.
     */
    public function testOwnerCanEdit(): void
    {
        $userId = '10000000-0000-0000-0000-000000000001';
        $user = $this->makeUser($userId, 10); // editor only, not admin
        $note = $this->makeNote($userId);

        $this->assertTrue($this->policy->canEdit($user, $note));
    }

    /**
     * A different user with no admin role cannot edit someone else's note.
     */
    public function testNonOwnerNonAdminCannotEdit(): void
    {
        $ownerId = '10000000-0000-0000-0000-000000000001';
        $otherId = '20000000-0000-0000-0000-000000000002';

        $other = $this->makeUser($otherId, 10); // editor only
        $note = $this->makeNote($ownerId);

        $this->assertFalse($this->policy->canEdit($other, $note));
    }

    /**
     * An admin (privileges ≤ 5) can edit any note regardless of ownership.
     */
    public function testAdminCanEditOthersNote(): void
    {
        $ownerId = '10000000-0000-0000-0000-000000000001';
        $adminId = '20000000-0000-0000-0000-000000000002';

        $admin = $this->makeUser($adminId, 2); // root → admin role
        $note = $this->makeNote($ownerId);

        $this->assertTrue($this->policy->canEdit($admin, $note));
    }

    /**
     * A user with privileges=5 (boundary for admin role) can edit others' notes.
     */
    public function testPrivileges5CanEdit(): void
    {
        $ownerId = '10000000-0000-0000-0000-000000000001';
        $adminId = '20000000-0000-0000-0000-000000000002';

        $admin = $this->makeUser($adminId, 5);
        $note = $this->makeNote($ownerId);

        $this->assertTrue($this->policy->canEdit($admin, $note));
    }

    /**
     * A user with privileges=6 (just above admin threshold) cannot edit.
     */
    public function testPrivileges6CannotEdit(): void
    {
        $ownerId = '10000000-0000-0000-0000-000000000001';
        $otherId = '20000000-0000-0000-0000-000000000002';

        $user = $this->makeUser($otherId, 6);
        $note = $this->makeNote($ownerId);

        $this->assertFalse($this->policy->canEdit($user, $note));
    }
}
