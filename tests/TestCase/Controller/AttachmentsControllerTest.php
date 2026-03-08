<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\AttachmentsController Test Case
 *
 * @uses \App\Controller\AttachmentsController
 */
class AttachmentsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures
     *
     * @var list<string>
     */
    protected array $fixtures = [
        'app.Attachments',
        'app.Users',
    ];

    private const ATTACH_ID = '3e7c2fba-1c29-4e5b-9bb2-000000000001';
    private const FOREIGN_ID = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    /**
     * Log in as the given user by injecting an Auth session entry.
     *
     * @param string $userId User UUID
     * @return void
     */
    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    // -------------------------------------------------------------------------
    // index (action does not exist in AttachmentsController)
    // -------------------------------------------------------------------------

    /**
     * Test index (no index action exists – documents that unauthenticated access
     * redirects to login and authenticated access returns an error).
     *
     * @return void
     * @uses \App\Controller\AttachmentsController
     */
    public function testIndex(): void
    {
        // Unauthenticated → redirect to login
        $this->get('/attachments');
        $this->assertRedirect();

        // Authenticated → missing action, expect an error response
        $this->login(USER_ADMIN);
        $this->get('/attachments');
        $this->assertResponseError();
    }

    // -------------------------------------------------------------------------
    // view
    // -------------------------------------------------------------------------

    /**
     * Test view method
     *
     * @return void
     * @uses \App\Controller\AttachmentsController::view()
     */
    public function testView(): void
    {
        // Unauthenticated → redirect to login
        $this->get('/attachments/view/' . self::ATTACH_ID);
        $this->assertRedirect();

        // Authenticated, existing record → 200
        $this->login(USER_ADMIN);
        $this->get('/attachments/view/' . self::ATTACH_ID);
        $this->assertResponseOk();

        // Non-existent record → 404
        $this->get('/attachments/view/nonexistent');
        $this->assertResponseCode(404);
    }

    // -------------------------------------------------------------------------
    // add  (handled by edit action without an id)
    // -------------------------------------------------------------------------

    /**
     * Test add (new attachment) via the edit action with no id.
     *
     * @return void
     * @uses \App\Controller\AttachmentsController::edit()
     */
    public function testAdd(): void
    {
        $url = '/attachments/edit?model=Test&foreign_id=' . self::FOREIGN_ID;

        // Unauthenticated → redirect to login
        $this->get($url);
        $this->assertRedirect();

        // Authenticated, missing query params → NotFoundException (404)
        $this->login(USER_ADMIN);
        $this->get('/attachments/edit');
        $this->assertResponseCode(404);

        // Authenticated, valid query params → 200 (add form)
        $this->get($url);
        $this->assertResponseOk();
    }

    // -------------------------------------------------------------------------
    // edit
    // -------------------------------------------------------------------------

    /**
     * Test edit method
     *
     * @return void
     * @uses \App\Controller\AttachmentsController::edit()
     */
    public function testEdit(): void
    {
        // Unauthenticated → redirect to login
        $this->get('/attachments/edit/' . self::ATTACH_ID);
        $this->assertRedirect();

        // Authenticated, existing record → 200 (edit form)
        $this->login(USER_ADMIN);
        $this->get('/attachments/edit/' . self::ATTACH_ID);
        $this->assertResponseOk();

        // Non-existent record → 404
        $this->get('/attachments/edit/nonexistent');
        $this->assertResponseCode(404);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    /**
     * Test delete method
     *
     * @return void
     * @uses \App\Controller\AttachmentsController::delete()
     */
    public function testDelete(): void
    {
        // Unauthenticated → redirect to login
        $this->get('/attachments/delete/' . self::ATTACH_ID);
        $this->assertRedirect();

        // Authenticated, existing record → deletes and redirects
        $this->login(USER_ADMIN);
        $this->get('/attachments/delete/' . self::ATTACH_ID);
        $this->assertRedirect();

        // Deleted record is gone → 404 on second attempt
        $this->get('/attachments/delete/' . self::ATTACH_ID);
        $this->assertResponseCode(404);
    }
}
