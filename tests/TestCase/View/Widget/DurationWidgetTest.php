<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Widget;

use App\View\Widget\DurationWidget;
use ArrayObject;
use Cake\TestSuite\TestCase;
use Cake\View\StringTemplate;
use Cake\View\View;

/**
 * App\View\Widget\DurationWidget Test Case
 */
class DurationWidgetTest extends TestCase
{
    // -------------------------------------------------------------------------
    // marshalDurationFields — static method, no DB needed
    // -------------------------------------------------------------------------

    /**
     * An array with the duration flag set is converted to integer seconds.
     */
    public function testMarshalDurationFieldsConverts(): void
    {
        $data = new ArrayObject([
            'work_duration' => [
                'hours' => '2',
                'minutes' => '30',
                'duration' => true,
            ],
        ]);

        DurationWidget::marshalDurationFields($data);

        // 2*3600 + 30*60 = 9000
        $this->assertSame(9000, $data['work_duration']);
    }

    /**
     * A zero-hour zero-minute duration resolves to 0 seconds.
     */
    public function testMarshalDurationFieldsZero(): void
    {
        $data = new ArrayObject([
            'work_duration' => [
                'hours' => '0',
                'minutes' => '0',
                'duration' => true,
            ],
        ]);

        DurationWidget::marshalDurationFields($data);

        $this->assertSame(0, $data['work_duration']);
    }

    /**
     * Fields without the duration flag are left untouched.
     */
    public function testMarshalDurationFieldsIgnoresNonDuration(): void
    {
        $data = new ArrayObject([
            'title' => 'My project',
            'count' => 42,
        ]);

        DurationWidget::marshalDurationFields($data);

        $this->assertSame('My project', $data['title']);
        $this->assertSame(42, $data['count']);
    }

    /**
     * Multiple duration fields in the same payload are all converted.
     */
    public function testMarshalDurationFieldsMultiple(): void
    {
        $data = new ArrayObject([
            'hours_a' => ['hours' => '1', 'minutes' => '0', 'duration' => true],
            'hours_b' => ['hours' => '0', 'minutes' => '45', 'duration' => true],
        ]);

        DurationWidget::marshalDurationFields($data);

        $this->assertSame(3600, $data['hours_a']);
        $this->assertSame(2700, $data['hours_b']);
    }

    // -------------------------------------------------------------------------
    // secureFields
    // -------------------------------------------------------------------------

    /**
     * secureFields returns the three sub-field names expected by CakePHP
     * security token handling.
     */
    public function testSecureFields(): void
    {
        $templates = new StringTemplate();
        $view = new View();
        $widget = new DurationWidget($templates, $view);

        $result = $widget->secureFields(['name' => 'work_duration']);

        $this->assertSame(
            ['work_duration.hours', 'work_duration.minutes', 'work_duration.duration'],
            $result,
        );
    }

    /**
     * secureFields uses the name key from $data.
     */
    public function testSecureFieldsCustomName(): void
    {
        $templates = new StringTemplate();
        $view = new View();
        $widget = new DurationWidget($templates, $view);

        $result = $widget->secureFields(['name' => 'duration']);

        $this->assertSame(
            ['duration.hours', 'duration.minutes', 'duration.duration'],
            $result,
        );
    }
}
