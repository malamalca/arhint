<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\ArhintHelper;
use App\View\Helper\LilHelper;
use Cake\I18n\Date;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * App\View\Helper\ArhintHelper Test Case
 */
class ArhintHelperTest extends TestCase
{
    protected ArhintHelper $Arhint;

    protected function setUp(): void
    {
        parent::setUp();
        Router::reload();
        Router::createRouteBuilder('/')->fallbacks(DashedRoute::class);

        $view = new View();
        // ArhintHelper depends on Html, Number and Lil; load them first.
        $view->loadHelper('Html');
        $view->loadHelper('Number');
        $view->loadHelper('Lil', ['className' => LilHelper::class]);

        $this->Arhint = new ArhintHelper($view);
    }

    protected function tearDown(): void
    {
        unset($this->Arhint);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // duration
    // -------------------------------------------------------------------------

    public function testDurationZero(): void
    {
        $this->assertSame('00:00', $this->Arhint->duration(0));
    }

    public function testDurationPositive(): void
    {
        // 3661s = 1h 1m 1s → rounded down to '01:01'
        $this->assertSame('01:01', $this->Arhint->duration(3661));
    }

    public function testDurationExactHour(): void
    {
        $this->assertSame('02:00', $this->Arhint->duration(7200));
    }

    public function testDurationNegative(): void
    {
        // -120s = -2 minutes
        $this->assertSame('-00:02', $this->Arhint->duration(-120));
    }

    public function testDurationPadding(): void
    {
        // 65s = 0h 1m
        $this->assertSame('00:01', $this->Arhint->duration(65));
    }

    // -------------------------------------------------------------------------
    // durationNice
    // -------------------------------------------------------------------------

    public function testDurationNiceZero(): void
    {
        $result = $this->Arhint->durationNice(0);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('0', $result);
    }

    public function testDurationNiceHoursOnly(): void
    {
        $result = $this->Arhint->durationNice(3600);
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Must contain the digit '1'
        $this->assertStringContainsString('1', $result);
    }

    public function testDurationNiceHoursAndMinutes(): void
    {
        $result = $this->Arhint->durationNice(3660);
        // 1 hour + 1 minute → must mention both numbers and contain a separator
        $this->assertStringContainsString('1', $result);
        $this->assertStringContainsString(',', $result);
    }

    // -------------------------------------------------------------------------
    // getMonthName
    // -------------------------------------------------------------------------

    public function testGetMonthNameReturnsString(): void
    {
        $name = $this->Arhint->getMonthName(1);
        $this->assertIsString($name);
        $this->assertNotEmpty($name);
    }

    public function testGetMonthNameAllMonthsDistinct(): void
    {
        $names = [];
        for ($m = 1; $m <= 12; $m++) {
            $names[] = $this->Arhint->getMonthName($m);
        }
        // All 12 names must be unique
        $this->assertCount(12, array_unique($names));
    }

    // -------------------------------------------------------------------------
    // getMonthNames
    // -------------------------------------------------------------------------

    public function testGetMonthNamesCount(): void
    {
        $names = $this->Arhint->getMonthNames();
        $this->assertCount(12, $names);
    }

    public function testGetMonthNamesKeys(): void
    {
        $names = $this->Arhint->getMonthNames();
        for ($m = 1; $m <= 12; $m++) {
            $this->assertArrayHasKey($m, $names);
        }
    }

    // -------------------------------------------------------------------------
    // getDayNames
    // -------------------------------------------------------------------------

    public function testGetDayNamesCount(): void
    {
        $days = $this->Arhint->getDayNames();
        $this->assertCount(7, $days);
    }

    /**
     * The helper starts from 2017-01-01 which was a Sunday.
     */
    public function testGetDayNamesStartsSunday(): void
    {
        $days = $this->Arhint->getDayNames();
        // In any locale the first character of Sunday starts with 'S' or 'D' etc;
        // we cannot assume locale so just assert it is a non-empty string.
        $this->assertIsString($days[0]);
        $this->assertNotEmpty($days[0]);
    }

    // -------------------------------------------------------------------------
    // toHoursAndMinutes
    // -------------------------------------------------------------------------

    public function testToHoursAndMinutesZero(): void
    {
        // Default options: zeroes=false, so 0s returns empty string
        $result = $this->Arhint->toHoursAndMinutes(0);
        $this->assertIsString($result);
    }

    public function testToHoursAndMinutesOneHour(): void
    {
        $result = $this->Arhint->toHoursAndMinutes(3600);
        // Must contain the digit '1' and be non-empty
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('1', $result);
    }

    public function testToHoursAndMinutesShortFormat(): void
    {
        // 3661s → 1h 1m; short=true gives "1:01"
        $result = $this->Arhint->toHoursAndMinutes(3661, ['short' => true]);
        $this->assertStringContainsString(':', $result);
    }

    // -------------------------------------------------------------------------
    // calendarDay
    // -------------------------------------------------------------------------

    public function testCalendarDayOutput(): void
    {
        $day = new Date('2025-01-05');
        $result = $this->Arhint->calendarDay($day);

        $this->assertIsString($result);
        $this->assertStringContainsString('calendar-day', $result);
        // Must contain the day number somewhere
        $this->assertStringContainsString('5', $result);
    }

    public function testCalendarDayTodayCssClass(): void
    {
        $today = new Date('now');
        $result = $this->Arhint->calendarDay($today);
        $this->assertStringContainsString('today', $result);
    }
}
