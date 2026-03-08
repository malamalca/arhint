<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\LilHelper;
use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\I18n\Time;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;
use Cake\View\View;

/**
 * App\View\Helper\LilHelper Test Case
 */
class LilHelperTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\View\Helper\LilHelper
     */
    protected LilHelper $Lil;

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Set up routes so Router::url() works in helper tests
        Router::reload();
        Router::createRouteBuilder('/')->fallbacks(DashedRoute::class);

        $view = new View();
        $this->Lil = new LilHelper($view);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Lil);
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // jsReady / jsReadyOut
    // -------------------------------------------------------------------------

    /**
     * A single jsReady block is returned verbatim by jsReadyOut.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::jsReady()
     * @uses \App\View\Helper\LilHelper::jsReadyOut()
     */
    public function testJsReadySingle(): void
    {
        $this->Lil->jsReady('alert("hello");');
        $this->assertSame('alert("hello");', $this->Lil->jsReadyOut());
    }

    /**
     * Multiple jsReady blocks are joined with the expected separator.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::jsReady()
     * @uses \App\View\Helper\LilHelper::jsReadyOut()
     */
    public function testJsReadyMultiple(): void
    {
        $this->Lil->jsReady('block1();');
        $this->Lil->jsReady('block2();');
        $out = $this->Lil->jsReadyOut();
        $this->assertStringContainsString('block1();', $out);
        $this->assertStringContainsString('block2();', $out);
    }

    /**
     * jsReadyOut on an empty helper returns an empty string.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::jsReadyOut()
     */
    public function testJsReadyOutEmpty(): void
    {
        $this->assertSame('', $this->Lil->jsReadyOut());
    }

    // -------------------------------------------------------------------------
    // dateFormat
    // -------------------------------------------------------------------------

    /**
     * dateFormat uses App.dateFormat and App.dateSeparator to build a JS-style
     * date format string (Y→yyyy, M→MM, D→dd).
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::dateFormat()
     */
    public function testDateFormatDotDMY(): void
    {
        Configure::write('App.dateFormat', 'DMY');
        Configure::write('App.dateSeparator', '.');
        $this->assertSame('dd.MM.yyyy', $this->Lil->dateFormat());
    }

    /**
     * @return void
     * @uses \App\View\Helper\LilHelper::dateFormat()
     */
    public function testDateFormatSlashMDY(): void
    {
        Configure::write('App.dateFormat', 'MDY');
        Configure::write('App.dateSeparator', '/');
        $this->assertSame('MM/dd/yyyy', $this->Lil->dateFormat());
    }

    /**
     * @return void
     * @uses \App\View\Helper\LilHelper::dateFormat()
     */
    public function testDateFormatDashYMD(): void
    {
        Configure::write('App.dateFormat', 'YMD');
        Configure::write('App.dateSeparator', '-');
        $this->assertSame('yyyy-MM-dd', $this->Lil->dateFormat());
    }

    // -------------------------------------------------------------------------
    // calendarDay
    // -------------------------------------------------------------------------

    /**
     * calendarDay wraps a weekday date into .calendar-day spans with the
     * correct month, day, and year content.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::calendarDay()
     */
    public function testCalendarDayWeekday(): void
    {
        // 2025-03-12 is a Wednesday (not a weekend)
        $date = new Date('2025-03-12');
        $html = $this->Lil->calendarDay($date);

        $this->assertStringContainsString('calendar-day', $html);
        $this->assertStringContainsString('2025', $html); // year
        $this->assertStringContainsString('12', $html); // day
        $this->assertStringNotContainsString('weekend', $html);
    }

    /**
     * calendarDay adds the 'weekend' class for a Saturday or Sunday.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::calendarDay()
     */
    public function testCalendarDayWeekend(): void
    {
        // 2025-03-15 is a Saturday
        $date = new Date('2025-03-15');
        $html = $this->Lil->calendarDay($date);

        $this->assertStringContainsString('weekend', $html);
    }

    // -------------------------------------------------------------------------
    // timePanel
    // -------------------------------------------------------------------------

    /**
     * timePanel from a string produces four digit spans in the correct order.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::timePanel()
     */
    public function testTimePanelFromString(): void
    {
        $html = $this->Lil->timePanel('08:30');

        $this->assertStringContainsString('timepanel', $html);
        $this->assertStringContainsString('class="hour1">0<', $html);
        $this->assertStringContainsString('class="hour2">8<', $html);
        $this->assertStringContainsString('class="minutes1">3<', $html);
        $this->assertStringContainsString('class="minutes2">0<', $html);
    }

    /**
     * timePanel from a Time object formats the same way as a string.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::timePanel()
     */
    public function testTimePanelFromTimeObject(): void
    {
        $time = new Time('14:05');
        $html = $this->Lil->timePanel($time);

        $this->assertStringContainsString('class="hour1">1<', $html);
        $this->assertStringContainsString('class="hour2">4<', $html);
        $this->assertStringContainsString('class="minutes1">0<', $html);
        $this->assertStringContainsString('class="minutes2">5<', $html);
    }

    /**
     * timePanel from a DateTime object formats correctly.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::timePanel()
     */
    public function testTimePanelFromDateTimeObject(): void
    {
        $dt = new DateTime('2025-06-01 23:59:00');
        $html = $this->Lil->timePanel($dt);

        $this->assertStringContainsString('class="hour1">2<', $html);
        $this->assertStringContainsString('class="hour2">3<', $html);
        $this->assertStringContainsString('class="minutes1">5<', $html);
        $this->assertStringContainsString('class="minutes2">9<', $html);
    }

    // -------------------------------------------------------------------------
    // autop
    // -------------------------------------------------------------------------

    /**
     * autop on an empty / whitespace-only string returns ''.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::autop()
     */
    public function testAutopEmptyString(): void
    {
        $this->assertSame('', $this->Lil->autop(''));
        $this->assertSame('', $this->Lil->autop('   '));
    }

    /**
     * autop wraps each line/paragraph in <p> tags.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::autop()
     */
    public function testAutopWrapsInParagraphs(): void
    {
        $html = $this->Lil->autop('Hello world');
        $this->assertStringContainsString('<p>', $html);
        $this->assertStringContainsString('Hello world', $html);
    }

    /**
     * When $br is false (0), remaining single newlines are NOT converted to <br />.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::autop()
     */
    public function testAutopBrFalseNoLineBreaks(): void
    {
        $html = $this->Lil->autop("line one\nline two", 0);
        $this->assertStringNotContainsString('<br />', $html);
    }

    /**
     * When $br is true (default), single newlines become <br />.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::autop()
     */
    public function testAutopBrTrueAddsLineBreaks(): void
    {
        $html = $this->Lil->autop("line one\nline two\nline three");
        $this->assertStringContainsString('<br />', $html);
    }

    // -------------------------------------------------------------------------
    // mbWordWrap
    // -------------------------------------------------------------------------

    /**
     * Lines shorter than $width are returned unchanged.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::mbWordWrap()
     */
    public function testMbWordWrapShortLine(): void
    {
        $result = $this->Lil->mbWordWrap('Hello', 20);
        $this->assertSame('Hello', $result);
    }

    /**
     * A long line is wrapped at word boundaries.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::mbWordWrap()
     */
    public function testMbWordWrapLongLine(): void
    {
        $result = $this->Lil->mbWordWrap('one two three four', 10);
        // Result must contain a newline break
        $this->assertStringContainsString("\n", $result);
        // All words are still present
        $this->assertStringContainsString('one', $result);
        $this->assertStringContainsString('four', $result);
    }

    /**
     * With result='array' the method returns an indexed array of lines.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::mbWordWrap()
     */
    public function testMbWordWrapResultArray(): void
    {
        $result = $this->Lil->mbWordWrap('one two three', 5, "\n", false, false);
        // Override: pass options as array
        $result = $this->Lil->mbWordWrap(
            'one two three',
            ['width' => 5, 'result' => 'array'],
        );
        $this->assertIsArray($result);
        $this->assertGreaterThan(1, count($result));
    }

    /**
     * maxlines truncates the output to the specified number of lines.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::mbWordWrap()
     */
    public function testMbWordWrapMaxLines(): void
    {
        $result = $this->Lil->mbWordWrap(
            'one two three four five six',
            ['width' => 5, 'result' => 'array', 'maxlines' => 2],
        );
        $this->assertCount(2, $result);
    }

    /**
     * prefix prepends to each array key.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::mbWordWrap()
     */
    public function testMbWordWrapPrefix(): void
    {
        $result = $this->Lil->mbWordWrap(
            'hello world',
            ['width' => 5, 'result' => 'array', 'prefix' => 'line_'],
        );
        foreach (array_keys($result) as $key) {
            $this->assertStringStartsWith('line_', (string)$key);
        }
    }

    /**
     * startwith sets the starting index of the result array.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::mbWordWrap()
     */
    public function testMbWordWrapStartWith(): void
    {
        $result = $this->Lil->mbWordWrap(
            'hello world',
            ['width' => 4, 'result' => 'array', 'startwith' => 5],
        );
        $this->assertArrayHasKey(5, $result);
    }

    // -------------------------------------------------------------------------
    // insertIntoArray (helper instance method)
    // -------------------------------------------------------------------------

    /**
     * Default (no option): union-merges at the end, existing keys not overwritten.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::insertIntoArray()
     */
    public function testHelperInsertNoOption(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        $this->Lil->insertIntoArray($arr, ['c' => 3]);
        $this->assertSame(['a' => 1, 'b' => 2, 'c' => 3], $arr);
    }

    /**
     * 'after' option inserts element after the named key.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::insertIntoArray()
     */
    public function testHelperInsertAfter(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->Lil->insertIntoArray($arr, ['x' => 10], ['after' => 'b']);
        $this->assertSame(['a' => 1, 'b' => 2, 'x' => 10, 'c' => 3], $arr);
    }

    /**
     * 'before' option inserts element before the named key.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::insertIntoArray()
     */
    public function testHelperInsertBefore(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->Lil->insertIntoArray($arr, ['x' => 10], ['before' => 'b']);
        $this->assertSame(['a' => 1, 'x' => 10, 'b' => 2, 'c' => 3], $arr);
    }

    /**
     * 'replace' option removes the named key and inserts element in its place.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::insertIntoArray()
     */
    public function testHelperInsertReplace(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        $this->Lil->insertIntoArray($arr, ['x' => 10], ['replace' => 'b']);
        $this->assertSame(['a' => 1, 'x' => 10, 'c' => 3], $arr);
        $this->assertArrayNotHasKey('b', $arr);
    }

    /**
     * 'after' with preserve=false re-indexes numeric element keys.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::insertIntoArray()
     */
    public function testHelperInsertAfterPreserveFalse(): void
    {
        $arr = [0 => 'a', 1 => 'b', 2 => 'c'];
        $this->Lil->insertIntoArray($arr, [5 => 'x'], ['after' => 1, 'preserve' => false]);
        $this->assertSame([0 => 'a', 1 => 'b', 2 => 'x', 3 => 'c'], $arr);
    }

    /**
     * 'before' with key-not-found leaves the array unchanged.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::insertIntoArray()
     */
    public function testHelperInsertBeforeKeyNotFound(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        $this->Lil->insertIntoArray($arr, ['x' => 10], ['before' => 'z']);
        $this->assertSame(['a' => 1, 'b' => 2], $arr);
    }

    // -------------------------------------------------------------------------
    // link
    // -------------------------------------------------------------------------

    /**
     * Plain call (no bracket syntax) delegates to Html::link.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::link()
     */
    public function testLinkPlain(): void
    {
        $html = $this->Lil->link('Click me', ['controller' => 'Pages', 'action' => 'index'], []);
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('Click me', $html);
        $this->assertStringContainsString('href=', $html);
    }

    /**
     * Bracket syntax replaces [Label] with a link sourced from the array arg.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::link()
     */
    public function testLinkBracketSyntaxSingle(): void
    {
        $html = $this->Lil->link(
            'See [details] here',
            [[['controller' => 'Items', 'action' => 'view', 1], []]],
        );
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('details', $html);
        $this->assertStringContainsString('See', $html);
        $this->assertStringContainsString('here', $html);
    }

    /**
     * Bracket syntax with two brackets replaced independently.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::link()
     */
    public function testLinkBracketSyntaxMultiple(): void
    {
        $html = $this->Lil->link(
            '[edit] or [delete]',
            [
                [['controller' => 'Items', 'action' => 'edit', 1], []],
                [['controller' => 'Items', 'action' => 'delete', 1], []],
            ],
        );
        $this->assertStringContainsString('edit', $html);
        $this->assertStringContainsString('delete', $html);
        // Both brackets replaced – no raw brackets left
        $this->assertStringNotContainsString('[edit]', $html);
        $this->assertStringNotContainsString('[delete]', $html);
    }

    /**
     * Bracket syntax with explicit $N index selector.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::link()
     */
    public function testLinkBracketSyntaxExplicitIndex(): void
    {
        // [$2Label] means use urls[2]
        $html = $this->Lil->link(
            'Click [$2second] here',
            [
                [['controller' => 'Items', 'action' => 'view', 1], []],
                [['controller' => 'Items', 'action' => 'view', 2], []],
                [['controller' => 'Items', 'action' => 'view', 3], []],
            ],
        );
        $this->assertStringContainsString('second', $html);
        $this->assertStringContainsString('/items/view/3', $html);
    }

    // -------------------------------------------------------------------------
    // deleteLink / editLink / viewLink
    // -------------------------------------------------------------------------

    /**
     * deleteLink produces an anchor with onclick confirm and 'delete' action URL.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::deleteLink()
     */
    public function testDeleteLinkDefault(): void
    {
        $html = $this->Lil->deleteLink(['controller' => 'Items', 'id' => 42]);
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('href=', $html);
        $this->assertStringContainsString('/items/delete', $html);
        $this->assertStringContainsString('onclick=', $html);
        $this->assertStringContainsString('confirm(', $html);
        // Custom confirm text was NOT passed, so the default localised message is used
        $this->assertStringNotContainsString('Really delete?', $html);
    }

    /**
     * deleteLink uses a custom confirmation message when supplied.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::deleteLink()
     */
    public function testDeleteLinkCustomConfirm(): void
    {
        $html = $this->Lil->deleteLink(
            ['controller' => 'Items', 'id' => 1],
            ['confirm' => 'Really delete?'],
        );
        $this->assertStringContainsString('Really delete?', $html);
        $this->assertStringNotContainsString('Are you sure', $html);
    }

    /**
     * editLink produces an anchor pointing to the edit action.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::editLink()
     */
    public function testEditLink(): void
    {
        $html = $this->Lil->editLink(['controller' => 'Items', 'id' => 5]);
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('/items/edit', $html);
        $this->assertStringContainsString('edit', $html);
    }

    /**
     * viewLink produces an anchor pointing to the view action.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::viewLink()
     */
    public function testViewLink(): void
    {
        $html = $this->Lil->viewLink(['controller' => 'Items', 'id' => 7]);
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('/items/view', $html);
        $this->assertStringContainsString('view', $html);
    }

    // -------------------------------------------------------------------------
    // popupLink
    // -------------------------------------------------------------------------

    /**
     * popupLink renders a popup-trigger anchor with the id/class and content.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::popupLink()
     */
    public function testPopupLink(): void
    {
        $html = $this->Lil->popupLink('mypopup', 'Open popup', ['controller' => 'Items', 'action' => 'index']);
        $this->assertStringContainsString('<a', $html);
        $this->assertStringContainsString('popup_mypopup', $html);
        $this->assertStringContainsString('popup_link', $html);
        $this->assertStringContainsString('Open popup', $html);
        $this->assertStringContainsString('/items', $html);
    }

    // -------------------------------------------------------------------------
    // popup (inline mode)
    // -------------------------------------------------------------------------

    /**
     * popup() with $inline=true returns the HTML string directly.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::popup()
     */
    public function testPopupInlineBasic(): void
    {
        $data = [
            'items' => [
                ['title' => 'Item A', 'url' => ['controller' => 'Items', 'action' => 'view', 1]],
                ['title' => 'Item B', 'url' => ['controller' => 'Items', 'action' => 'view', 2]],
            ],
        ];
        $html = $this->Lil->popup('testpopup', $data, true);

        $this->assertIsString($html);
        $this->assertStringContainsString('popup_testpopup', $html);
        $this->assertStringContainsString('Item A', $html);
        $this->assertStringContainsString('Item B', $html);
    }

    /**
     * popup() with a plain-string item uses the popup-item-plain template.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::popup()
     */
    public function testPopupInlinePlainStringItem(): void
    {
        $data = ['items' => ['Just some text']];
        $html = $this->Lil->popup('plain', $data, true);
        $this->assertStringContainsString('Just some text', $html);
        $this->assertStringContainsString('<li>', $html);
    }

    /**
     * popup() with $inline=false appends to the 'popups' view block (returns null).
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::popup()
     */
    public function testPopupBlockMode(): void
    {
        $data = ['items' => [['title' => 'Block item', 'url' => '/']]];
        $result = $this->Lil->popup('blockpopup', $data, false);
        $this->assertNull($result);
    }

    // -------------------------------------------------------------------------
    // menu
    // -------------------------------------------------------------------------

    /**
     * menu() renders a navbar <ul> with a visible item.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::menu()
     */
    public function testMenuBasicItem(): void
    {
        $data = [
            'params' => [],
            'items' => [
                'home' => [
                    'visible' => true,
                    'title' => 'Home',
                    'url' => ['controller' => 'Pages', 'action' => 'index'],
                    'params' => [],
                ],
            ],
        ];
        $html = $this->Lil->menu($data);
        $this->assertStringContainsString('<ul', $html);
        $this->assertStringContainsString('Home', $html);
        $this->assertStringContainsString('/pages', $html);
    }

    /**
     * Invisible items (visible=false) are not rendered.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::menu()
     */
    public function testMenuInvisibleItemHidden(): void
    {
        $data = [
            'params' => [],
            'items' => [
                'secret' => [
                    'visible' => false,
                    'title' => 'Secret',
                    'url' => ['controller' => 'Pages', 'action' => 'index'],
                    'params' => [],
                ],
            ],
        ];
        $html = $this->Lil->menu($data);
        $this->assertStringNotContainsString('Secret', $html);
    }

    /**
     * menu() renders a submenu when 'submenu' key is present.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::menu()
     */
    public function testMenuWithSubmenu(): void
    {
        $data = [
            'params' => [],
            'items' => [
                'reports' => [
                    'visible' => true,
                    'title' => 'Reports',
                    'params' => [],
                    'submenu' => [
                        ['title' => 'Monthly', 'url' => ['controller' => 'Reports', 'action' => 'monthly'], 'params' => []],
                        ['title' => 'Annual', 'url' => ['controller' => 'Reports', 'action' => 'annual'], 'params' => []],
                    ],
                ],
            ],
        ];
        $html = $this->Lil->menu($data);
        $this->assertStringContainsString('Reports', $html);
        $this->assertStringContainsString('Monthly', $html);
        $this->assertStringContainsString('Annual', $html);
        $this->assertStringContainsString('popup_link', $html);
    }

    // -------------------------------------------------------------------------
    // table
    // -------------------------------------------------------------------------

    /**
     * table() renders a <table> with the correct structure for a basic table.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::table()
     */
    public function testTableBasic(): void
    {
        $data = [
            'head' => [
                'rows' => [
                    ['columns' => [['html' => 'Name'], ['html' => 'Amount']]],
                ],
            ],
            'body' => [
                'rows' => [
                    ['columns' => [['html' => 'Alice'], ['html' => '100']]],
                    ['columns' => [['html' => 'Bob'], ['html' => '200']]],
                ],
            ],
        ];
        $html = $this->Lil->table($data);

        $this->assertStringContainsString('<table', $html);
        $this->assertStringContainsString('</table>', $html);
        $this->assertStringContainsString('<thead', $html);
        $this->assertStringContainsString('<tbody>', $html);
        $this->assertStringContainsString('Name', $html);
        $this->assertStringContainsString('Amount', $html);
        $this->assertStringContainsString('Alice', $html);
        $this->assertStringContainsString('Bob', $html);
    }

    /**
     * table() renders a <tfoot> section when 'foot' data is supplied.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::table()
     */
    public function testTableWithFoot(): void
    {
        $data = [
            'head' => [
                'rows' => [
                    ['columns' => [['html' => 'Total']]],
                ],
            ],
            'body' => [
                'rows' => [
                    ['columns' => [['html' => '42']]],
                ],
            ],
            'foot' => [
                'rows' => [
                    ['columns' => [['html' => 'Sum: 42']]],
                ],
            ],
        ];
        $html = $this->Lil->table($data);
        $this->assertStringContainsString('<tfoot>', $html);
        $this->assertStringContainsString('Sum: 42', $html);
    }

    /**
     * table() outputs pre/post strings around the table.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::table()
     */
    public function testTablePrePost(): void
    {
        $data = [
            'pre' => '<div class="wrapper">',
            'post' => '</div>',
            'head' => ['rows' => []],
            'body' => ['rows' => []],
        ];
        $html = $this->Lil->table($data);
        $this->assertStringContainsString('<div class="wrapper">', $html);
        $this->assertStringContainsString('</div>', $html);
        // pre must come before the table
        $this->assertLessThan(strpos($html, '<table'), strpos($html, '<div class="wrapper">'));
    }

    /**
     * table() renders string column cells correctly (shorthand notation).
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::table()
     */
    public function testTableStringColumns(): void
    {
        $data = [
            'head' => ['rows' => []],
            'body' => [
                'rows' => [
                    ['columns' => ['<strong>bold</strong>', 'plain']],
                ],
            ],
        ];
        $html = $this->Lil->table($data);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('plain', $html);
    }

    /**
     * table() adds class/params from data to the table element.
     *
     * @return void
     * @uses \App\View\Helper\LilHelper::table()
     */
    public function testTableParams(): void
    {
        $data = [
            'params' => ['class' => 'my-table'],
            'head' => ['rows' => []],
            'body' => ['rows' => []],
        ];
        $html = $this->Lil->table($data);
        $this->assertStringContainsString('my-table', $html);
    }
}
