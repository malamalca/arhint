<?php
declare(strict_types=1);

namespace Calendar\Test\TestCase\Lib;

use Calendar\Lib\SabreDAVSyncCalendars;
use Cake\TestSuite\TestCase;

/**
 * Calendar\Lib\SabreDAVSyncCalendars Test Case
 *
 * @uses \Calendar\Lib\SabreDAVSyncCalendars
 */
class SabreDAVSyncCalendarsTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'plugin.Calendar.Events',
    ];

    protected SabreDAVSyncCalendars $backend;

    public function setUp(): void
    {
        parent::setUp();
        $this->backend = new SabreDAVSyncCalendars();
    }

    public function tearDown(): void
    {
        unset($this->backend);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // getCalendarsForUser
    // -------------------------------------------------------------------------

    /**
     * Returns one calendar entry for a known user.
     *
     * @return void
     */
    public function testGetCalendarsForUserReturnsCalendar(): void
    {
        $calendars = $this->backend->getCalendarsForUser('principals/admin');

        $this->assertCount(1, $calendars);
        $this->assertSame('default', $calendars[0]['uri']);
        $this->assertSame('principals/admin', $calendars[0]['principaluri']);
        $this->assertSame(USER_ADMIN, $calendars[0]['id']);
    }

    /**
     * Returns an empty array for an unknown principal.
     *
     * @return void
     */
    public function testGetCalendarsForUserUnknownPrincipal(): void
    {
        $calendars = $this->backend->getCalendarsForUser('principals/nobody');
        $this->assertSame([], $calendars);
    }

    // -------------------------------------------------------------------------
    // getCalendarObjects
    // -------------------------------------------------------------------------

    /**
     * Returns objects for the calendar owner.
     *
     * @return void
     */
    public function testGetCalendarObjectsReturnsRows(): void
    {
        $objects = $this->backend->getCalendarObjects(USER_ADMIN);

        $this->assertNotEmpty($objects);
        $obj = $objects[0];
        $this->assertArrayHasKey('id', $obj);
        $this->assertArrayHasKey('uri', $obj);
        $this->assertArrayHasKey('etag', $obj);
        $this->assertArrayHasKey('size', $obj);
        $this->assertArrayHasKey('lastmodified', $obj);
        $this->assertStringEndsWith('.ics', $obj['uri']);
        $this->assertSame('vevent', $obj['component']);
        // calendardata is NOT required for list responses
        $this->assertArrayNotHasKey('calendardata', $obj);
    }

    /**
     * Returns an empty array for a calendar with no events.
     *
     * @return void
     */
    public function testGetCalendarObjectsEmptyCalendar(): void
    {
        $objects = $this->backend->getCalendarObjects('00000000-0000-0000-0000-000000000000');
        $this->assertSame([], $objects);
    }

    // -------------------------------------------------------------------------
    // getCalendarObject
    // -------------------------------------------------------------------------

    /**
     * Returns full iCal data for a known event.
     *
     * @return void
     */
    public function testGetCalendarObjectReturnsIcalData(): void
    {
        $uri = '185383a4-38c8-4194-9516-52c9069bc3bf.ics';
        $obj = $this->backend->getCalendarObject(USER_ADMIN, $uri);

        $this->assertNotNull($obj);
        $this->assertArrayHasKey('calendardata', $obj);
        $this->assertStringContainsString('BEGIN:VCALENDAR', $obj['calendardata']);
        $this->assertStringContainsString('BEGIN:VEVENT', $obj['calendardata']);
        $this->assertStringContainsString('185383a4-38c8-4194-9516-52c9069bc3bf', $obj['calendardata']);
        $this->assertSame('vevent', $obj['component']);
    }

    /**
     * Returns null for a non-existent object URI.
     *
     * @return void
     */
    public function testGetCalendarObjectReturnsNullWhenMissing(): void
    {
        $obj = $this->backend->getCalendarObject(USER_ADMIN, 'nonexistent.ics');
        $this->assertNull($obj);
    }

    // -------------------------------------------------------------------------
    // getMultipleCalendarObjects
    // -------------------------------------------------------------------------

    /**
     * Returns an array with one entry when given one valid URI.
     *
     * @return void
     */
    public function testGetMultipleCalendarObjects(): void
    {
        $uris = ['185383a4-38c8-4194-9516-52c9069bc3bf.ics'];
        $objects = $this->backend->getMultipleCalendarObjects(USER_ADMIN, $uris);

        $this->assertCount(1, $objects);
        $this->assertArrayHasKey('calendardata', $objects[0]);
    }

    // -------------------------------------------------------------------------
    // createCalendarObject / updateCalendarObject / deleteCalendarObject
    // -------------------------------------------------------------------------

    /**
     * Creates a new calendar object and verifies it can be fetched back.
     *
     * @return void
     */
    public function testCreateCalendarObject(): void
    {
        $icalData = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//Test//EN',
            'BEGIN:VEVENT',
            'UID:test-create-uid-001',
            'SUMMARY:Test Create Event',
            'DTSTART:20260401T100000Z',
            'DTEND:20260401T110000Z',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        $etag = $this->backend->createCalendarObject(
            USER_ADMIN,
            'test-create-uid-001.ics',
            $icalData,
        );

        $this->assertNotNull($etag);
        $this->assertMatchesRegularExpression('/^"[a-f0-9]{32}"$/', $etag);

        // Verify retrieval
        $obj = $this->backend->getCalendarObject(USER_ADMIN, 'test-create-uid-001.ics');
        $this->assertNotNull($obj);
        $this->assertStringContainsString('Test Create Event', $obj['calendardata']);
    }

    /**
     * Updates an existing calendar object.
     *
     * @return void
     */
    public function testUpdateCalendarObject(): void
    {
        $icalData = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test//Test//EN',
            'BEGIN:VEVENT',
            'UID:185383a4-38c8-4194-9516-52c9069bc3bf',
            'SUMMARY:Updated Title',
            'DTSTART:20260402T090000Z',
            'DTEND:20260402T100000Z',
            'END:VEVENT',
            'END:VCALENDAR',
        ]);

        $etag = $this->backend->updateCalendarObject(
            USER_ADMIN,
            '185383a4-38c8-4194-9516-52c9069bc3bf.ics',
            $icalData,
        );

        $this->assertNotNull($etag);

        $obj = $this->backend->getCalendarObject(USER_ADMIN, '185383a4-38c8-4194-9516-52c9069bc3bf.ics');
        $this->assertStringContainsString('Updated Title', $obj['calendardata']);
    }

    /**
     * Deletes an existing calendar object.
     *
     * @return void
     */
    public function testDeleteCalendarObject(): void
    {
        $uri = '185383a4-38c8-4194-9516-52c9069bc3bf.ics';

        $this->backend->deleteCalendarObject(USER_ADMIN, $uri);

        $obj = $this->backend->getCalendarObject(USER_ADMIN, $uri);
        $this->assertNull($obj);
    }

    /**
     * Deleting a non-existent object does not throw.
     *
     * @return void
     */
    public function testDeleteCalendarObjectNonExistent(): void
    {
        $this->backend->deleteCalendarObject(USER_ADMIN, 'does-not-exist.ics');
        // no exception = pass
        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // No-op / stub methods
    // -------------------------------------------------------------------------

    /**
     * createCalendar returns false (not supported).
     *
     * @return void
     */
    public function testCreateCalendarReturnsFalse(): void
    {
        $result = $this->backend->createCalendar('principals/admin', 'new-cal', []);
        $this->assertFalse($result);
    }

    /**
     * getChangesForCalendar returns null (no change-tracking).
     *
     * @return void
     */
    public function testGetChangesForCalendarReturnsNull(): void
    {
        $result = $this->backend->getChangesForCalendar(USER_ADMIN, '1', 1);
        $this->assertNull($result);
    }
}
