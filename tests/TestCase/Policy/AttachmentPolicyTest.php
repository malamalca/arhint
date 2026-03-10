<?php
declare(strict_types=1);

namespace App\Test\TestCase\Policy;

use App\Model\Entity\Attachment;
use App\Policy\AttachmentPolicy;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;

/**
 * App\Policy\AttachmentPolicy Test Case
 *
 * AttachmentPolicy rules:
 *   - canEdit / canDelete  → authUser->hasRole('editor')  (privileges ≤ 10)
 *   - canView / canDownload→ AttachmentsTable::isOwnedBy($attachment, $authUser->company_id)
 */
class AttachmentPolicyTest extends TestCase
{
    /**
     * @var string[]
     */
    protected array $fixtures = [
        'app.Users',
        'app.Attachments',
    ];

    protected AttachmentPolicy $policy;

    /** ID constants from UsersFixture */
    private const USER_ADMIN = '048acacf-d87c-4088-a3a7-4bab30f6a040';
    private const USER_COMMON = '048acacf-d87c-4088-a3a7-4bab30f6a041';

    public function setUp(): void
    {
        parent::setUp();
        $this->policy = new AttachmentPolicy();
    }

    // -------------------------------------------------------------------------
    // canEdit / canDelete
    // -------------------------------------------------------------------------

    /**
     * Admin (privileges=2) has the 'editor' role → can edit.
     */
    public function testCanEditForAdmin(): void
    {
        $Users = TableRegistry::getTableLocator()->get('Users');
        /** @var \App\Model\Entity\User $admin */
        $admin = $Users->get(self::USER_ADMIN);

        $attachment = new Attachment(['id' => '00000000-0000-0000-0000-000000000001']);
        $this->assertTrue($this->policy->canEdit($admin, $attachment));
    }

    /**
     * Common user (privileges=10) still has 'editor' role → can edit.
     */
    public function testCanEditForCommonUser(): void
    {
        $Users = TableRegistry::getTableLocator()->get('Users');
        /** @var \App\Model\Entity\User $common */
        $common = $Users->get(self::USER_COMMON);

        $attachment = new Attachment(['id' => '00000000-0000-0000-0000-000000000001']);
        $this->assertTrue($this->policy->canEdit($common, $attachment));
    }

    /**
     * canDelete mirrors canEdit.
     */
    public function testCanDeleteForAdmin(): void
    {
        $Users = TableRegistry::getTableLocator()->get('Users');
        /** @var \App\Model\Entity\User $admin */
        $admin = $Users->get(self::USER_ADMIN);

        $attachment = new Attachment(['id' => '00000000-0000-0000-0000-000000000001']);
        $this->assertTrue($this->policy->canDelete($admin, $attachment));
    }
}
