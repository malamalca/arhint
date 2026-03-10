<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\ORM\TableRegistry;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * App\Controller\DashboardNotesController Test Case
 *
 * @uses \App\Controller\DashboardNotesController
 */
class DashboardNotesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
        'app.DashboardNotes',
    ];

    private const NOTE_ADMIN = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeee01';
    private const NOTE_COMMON = 'aaaaaaaa-bbbb-4ccc-8ddd-eeeeeeeeee02';

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableCsrfToken();
    }

    private function login(string $userId): void
    {
        $user = TableRegistry::getTableLocator()->get('Users')->get($userId);
        $this->session(['Auth' => $user]);
    }

    // -------------------------------------------------------------------------
    // edit – add (no id)
    // -------------------------------------------------------------------------

    /**
     * Test edit (add) unauthenticated redirects to login.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditAddUnauthenticated(): void
    {
        $this->get('/dashboard-notes/edit');
        $this->assertRedirect();
    }

    /**
     * Test edit (add) authenticated GET renders the form.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditAddGet(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/dashboard-notes/edit');
        $this->assertResponseOk();
    }

    /**
     * Test edit (add) authenticated POST saves and redirects.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditAddPost(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'note' => 'My new dashboard note',
        ];

        $this->post(
            ['controller' => 'DashboardNotes', 'action' => 'edit'],
            $data,
        );
        $this->assertRedirect();

        // Note was persisted
        $Notes = $this->getTableLocator()->get('DashboardNotes');
        $this->assertTrue($Notes->exists(['note' => 'My new dashboard note']));
    }

    // -------------------------------------------------------------------------
    // edit – update existing (with id)
    // -------------------------------------------------------------------------

    /**
     * Test edit (update) unauthenticated redirects to login.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditUpdateUnauthenticated(): void
    {
        $this->get('/dashboard-notes/edit/' . self::NOTE_ADMIN);
        $this->assertRedirect();
    }

    /**
     * Test edit (update) authenticated GET renders form with existing data.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditUpdateGet(): void
    {
        $this->login(USER_ADMIN);
        $this->get('/dashboard-notes/edit/' . self::NOTE_ADMIN);
        $this->assertResponseOk();
    }

    /**
     * Test that a user can only edit their own note (policy: same user or admin).
     * USER_COMMON has privileges=10 → not admin → cannot edit another user's note.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditUpdatePolicyDenied(): void
    {
        // USER_COMMON trying to edit USER_ADMIN's note → forbidden
        $this->login(USER_COMMON);
        $this->get('/dashboard-notes/edit/' . self::NOTE_ADMIN);
        $this->assertResponseError();
    }

    /**
     * Test edit (update) authenticated POST saves and redirects.
     *
     * @return void
     * @uses \App\Controller\DashboardNotesController::edit()
     */
    public function testEditUpdatePost(): void
    {
        $this->login(USER_ADMIN);
        $this->enableSecurityToken();
        $this->enableCsrfToken();

        $data = [
            'note' => 'Updated note text',
        ];

        $this->post(
            ['controller' => 'DashboardNotes', 'action' => 'edit', self::NOTE_ADMIN],
            $data,
        );
        $this->assertRedirect();

        $Notes = $this->getTableLocator()->get('DashboardNotes');
        $note = $Notes->get(self::NOTE_ADMIN);
        $this->assertEquals('Updated note text', $note->note);
    }
}
