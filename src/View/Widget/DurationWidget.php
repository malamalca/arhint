<?php
declare(strict_types=1);

/**
 * LilDateWidget Form widget for date input
 *
 * PHP version 5.3
 *
 * @category Class
 * @package  App
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
namespace App\View\Widget;

use ArrayObject;
use Cake\View\Form\ContextInterface;
use Cake\View\StringTemplate;
use Cake\View\View;
use Cake\View\Widget\WidgetInterface;

/**
 * DurationWidget Form widget for duration input
 *
 * @category Class
 * @package  App
 * @author   Arhim d.o.o. <info@arhim.si>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://www.arhint.si
 */
class DurationWidget implements WidgetInterface
{
    /**
     * StringTemplate instance.
     *
     * @var \Cake\View\StringTemplate
     */
    protected StringTemplate $templates;

    /**
     * View instance.
     *
     * @var \Cake\View\View
     */
    protected View $view;

    /**
     * Constructor.
     *
     * @param \Cake\View\StringTemplate $templates Templates list.
     * @param \Cake\View\View $view Reference to view.
     */
    public function __construct(StringTemplate $templates, View $view)
    {
        $this->templates = $templates;
        $this->view = $view;
    }

    /**
     * Render a duration field.
     *
     * Any other keys provided in $data will be converted into HTML attributes.
     *
     * @param array<string, mixed> $data The data to build a control with.
     * @param \Cake\View\Form\ContextInterface $context The form context.
     * @return string
     */
    public function render(array $data, ContextInterface $context): string
    {
        $defaultAttrs = [
            'name' => '',
            'size' => 2,
            'min' => 0,
            'pattern' => '[\d]*',
        ];

        $filteredAttrs = $data + $defaultAttrs;
        if (isset($filteredAttrs['type'])) {
            unset($filteredAttrs['type']);
        }
        if (isset($filteredAttrs['id'])) {
            unset($filteredAttrs['id']);
        }

        $hours = '';
        if (!empty($data['val'])) {
            if (is_array($data['val']) && !empty($data['val']['hours'])) {
                $hours = $data['val']['hours'];
            } elseif (is_int($data['val'])) {
                $hours = floor($data['val'] / 3600);
            }
        }

        $attrHours = $filteredAttrs + [
            'value' => $hours,
            'placeholder' => __('Hours'),
            'class' => 'duration-hours',
        ];

        $minutes = '';
        if (!empty($data['val'])) {
            if (is_array($data['val']) && !empty($data['val']['minutes'])) {
                $minutes = $data['val']['minutes'];
            } elseif (is_int($data['val'])) {
                $minutes = floor($data['val'] / 60) % 60;
            }
        }
        $attrMins = $filteredAttrs + [
            'value' => $minutes,
            'placeholder' => __('Minutes'),
            'max' => 59,
            'class' => 'duration-minutes',
        ];

        $hoursControl = $this->templates->format(
            'input',
            [
                'type' => 'number',
                'name' => $data['name'] . '[hours]',
                'id' => $data['name'] . '-hours',
                'attrs' => $this->templates->formatAttributes($attrHours, ['name']),
            ],
        );

        $minutesControl = $this->templates->format(
            'input',
            [
                'type' => 'number',
                'name' => $data['name'] . '[minutes]',
                'id' => $data['name'] . '-minutes',
                'attrs' => $this->templates->formatAttributes($attrMins, ['name']),
            ],
        );

        $label = $this->templates->format(
            'label',
            ['text' => $data['label'] ?? ''],
        );

        $hidden = $this->templates->format(
            'hidden',
            [
                'name' => $data['name'] . '[duration]',
                'attrs' => $this->templates->formatAttributes(['value' => true]),
            ],
        );

        $ret = $this->templates->format('durationWrapper', [
            'title2' => $label,
            'hours' => $hoursControl,
            'minutes' => $minutesControl,
            'hidden' => $hidden,
        ]);

        return $ret;
    }

    /**
     * {@inheritDoc}
     *
     * @param array<string, mixed> $data Fields data.
     * @return list<string>
     */
    public function secureFields(array $data): array
    {
        // fdsfd
        return [$data['name'] . '.hours', $data['name'] . '.minutes', $data['name'] . '.duration'];
    }

    /**
     * Marshall duration data from array to integer seconds.
     *
     * @param \ArrayObject<string, mixed> $data Data to be marshalled.
     * @return void
     */
    public static function marshalDurationFields(ArrayObject $data): void
    {
        foreach ($data as $fieldName => $fieldValue) {
            if (is_array($fieldValue) && !empty($fieldValue['duration'])) {
                $data[$fieldName] = (int)$data[$fieldName]['hours'] * 3600 + (int)$data[$fieldName]['minutes'] * 60;
            }
        }
    }
}
