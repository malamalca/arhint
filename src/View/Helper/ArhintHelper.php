<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Model\Entity\User;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\View\Helper;

/**
 * @property \Cake\View\Helper\HtmlHelper $Html
 */
class ArhintHelper extends Helper
{
    public const DATE_NAME_FULL = 0;
    public const DATE_NAME_SHORT = 1;
    public const DATE_NAME_ABBREV = 2;

    /**
     * @var array<string> $helpers
     */
    protected array $helpers = ['Html'];

    /**
     * Returns duration in form HH:MM
     *
     * @param int $seconds Duration in seconds
     * @return string
     */
    public function duration(int $seconds): string
    {
        $sign = $seconds < 0 ? '-' : '';

        $hours = str_pad((string)floor(abs($seconds) / 3600), 2, '0', STR_PAD_LEFT);
        $minutes = str_pad((string)(floor(abs($seconds) / 60) % 60), 2, '0', STR_PAD_LEFT);

        return $sign . $hours . ':' . $minutes;
    }

    /**
     * Returns month name
     *
     * @param int $month Month number from 1..12
     * @param int $format Date name format option
     * @return string|int
     */
    public function getMonthName(int $month, int $format = self::DATE_NAME_FULL): int|string
    {
        $_time = new Date('2018-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-01');
        switch ($format) {
            case self::DATE_NAME_FULL:
                $_format = 'MMMM';
                break;
            case self::DATE_NAME_SHORT:
                $_format = 'MMM';
                break;
            case self::DATE_NAME_ABBREV:
                $_format = 'MMMMM';
                break;
            default:
                $_format = 'MMMM';
        }

        return $_time->i18nFormat($_format);
    }

    /**
     * Returns month names
     *
     * @param int $format Date name format option
     * @return array<int, string>
     */
    public function getMonthNames(int $format = self::DATE_NAME_FULL): array
    {
        $ret = [];

        switch ($format) {
            case self::DATE_NAME_FULL:
                $_format = 'MMMM';
                break;
            case self::DATE_NAME_SHORT:
                $_format = 'MMM';
                break;
            case self::DATE_NAME_ABBREV:
                $_format = 'MMMMM';
                break;
            default:
                $_format = 'MMMM';
        }

        for ($month = 1; $month < 13; $month++) {
            $dateString = '2018-' . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-01';
            $ret[$month] = (string)(new Date($dateString))->i18nFormat($_format);
        }

        return $ret;
    }

    /**
     * Returns day names
     *
     * @param int $format Length of formatting chars
     * @return array<int, string>
     */
    public function getDayNames(int $format = self::DATE_NAME_FULL): array
    {
        switch ($format) {
            case self::DATE_NAME_FULL:
                $_format = 'eeee';
                break;
            case self::DATE_NAME_SHORT:
                $_format = 'eee';
                break;
            case self::DATE_NAME_ABBREV:
                $_format = 'eeeee';
                break;
            default:
                $_format = 'eeee';
        }

        $ret = [];
        $aDate = new Date('2017-01-01'); // that was a sunday
        for ($i = 0; $i < 7; $i++) {
            $ret[] = (string)$aDate->i18nFormat($_format);
            $aDate = $aDate->addDays(1);
        }

        return $ret;
    }

    /**
     * Returns hourly representation of seconds
     *
     * @param int $seconds Specified seconds
     * @param array<string, mixed> $options Options array
     * @return string
     */
    public function toHoursAndMinutes(int $seconds = 0, array $options = []): string
    {
        $defaultOptions = [
            'separator' => ', ',
            'whitespace' => ' ',
            'zeroes' => false,
            'short' => false,
            'show_hours' => true,
            'pad_mins' => true,
            'pad_hours' => false,
        ];
        $options = array_merge($defaultOptions, $options);

        if ($options['pad_mins'] === true) {
            $options['pad_mins'] = '0';
        }
        if ($options['pad_hours'] === true) {
            $options['pad_hours'] = '0';
        }

        $negative = $seconds < 0;
        $seconds = abs($seconds);
        $ret = '';
        $hours = 0;
        $minutes = 0;

        $hours = (int)floor($seconds / 3600);
        $minutes = (int)round(($seconds - ($hours * 3600)) / 60);
        if ((($hours > 0) && $options['show_hours']) || $options['zeroes'] === 'both') {
            $hrs_display = $hours;
            if ($options['pad_hours'] !== false) {
                $hrs_display = str_pad((string)$hours, 2, $options['pad_hours'], STR_PAD_LEFT);
            }

            if ($minutes > 0 || $options['zeroes'] === 'both') {
                $mins_display = $minutes;
                if ($options['pad_mins'] !== false) {
                    $mins_display = str_pad((string)$minutes, 2, $options['pad_mins'], STR_PAD_LEFT);
                }

                if ($options['short']) {
                    $ret .= $hrs_display . ':' . $mins_display;
                } else {
                    $ret .=
                        $hrs_display . $options['whitespace'] .
                        __n('hr', 'hrs', $hours) .
                        $options['separator'] .
                        $mins_display . $options['whitespace'] .
                        __n('min', 'mins', $minutes);
                }
            } else {
                if ($options['short']) {
                    $ret .= $hrs_display;
                } else {
                    $ret .= $hrs_display . $options['whitespace'] . __n('hr', 'hrs', $hours);
                }
            }
        } elseif ($minutes > 0) {
            $minutes = $mins_display = $minutes + $hours * 60;
            if (isset($options['pad_mins'])) {
                $mins_display = str_pad((string)$minutes, 2, $options['pad_mins'], STR_PAD_LEFT);
            }

            if ($options['short']) {
                $ret .= $mins_display;
            } else {
                $ret .= $mins_display . $options['whitespace'] . __n('min', 'mins', $minutes);
            }
        }

        if ($hours == 0 && $minutes == 0 && $options['zeroes'] !== false && $options['zeroes'] !== 'both') {
            if ($options['zeroes'] === 'minutes') {
                if ($options['short']) {
                    $ret .= '00';
                } else {
                    $ret .= '0' . $options['whitespace'] . __n('min', 'mins', 0);
                }
            } else {
                if ($options['short']) {
                    $ret .= '00';
                } else {
                    $ret .= '0' . $options['whitespace'] . __n('hr', 'hrs', 0);
                }
            }
        }

        if ($negative) {
            $ret = '-' . $options['whitespace'] . $ret;
        }

        return $ret;
    }

    /**
     * Output date in format for css component
     *
     * @param \Cake\I18n\DateTime|\Cake\I18n\Date $day Specified day.
     * @param bool $isHoliday Marks date as holiday. Defaults to false.
     * @return string
     */
    public function calendarDay(DateTime|Date $day, bool $isHoliday = false): string
    {
        $ret = sprintf(
            '<span class="calendar-day%5$s">' .
                '<span class="year-name%4$s">%3$s</span>' .
                '<span class="day">%2$s</span><span class="month">%1$s</span>' .
                '</span>',
            $day->i18nFormat('MMMM'),
            $day->i18nFormat('dd'),
            $day->i18nFormat('y'),
            ($day->isWeekend() ? ' weekend' : ''),
            ($day->isToday() ? ' today' : ''),
        );

        return $ret;
    }

    /**
     * Output hour
     *
     * @param \Cake\I18n\DateTime|string $time Specified time.
     * @return string
     */
    public function timePanel(DateTime|string $time): string
    {
        if (!is_string($time)) {
            $time = (string)$time->i18nFormat('HH:mm');
        }

        $ret = sprintf(
            '<div class="timepanel"><span class="hour1">%1$s</span><span class="hour2">%2$s</span>' .
            '<span class="separator">:</span>' .
            '<span class="minutes1">%3$s</span><span class="minutes2">%4$s</span></div>',
            substr($time, 0, 1),
            substr($time, 1, 1),
            substr($time, 3, 1),
            substr($time, 4, 1),
        );

        return $ret;
    }

    /**
     * Output line with time and description
     *
     * @param \Cake\I18n\DateTime $dateTime Datetime of registrations
     * @param string $descript Descriptions
     * @return string
     */
    public function workdayRow(DateTime $dateTime, string $descript): string
    {
        $ret = sprintf(
            //'<div><span class="time">%1$s</span>%2$s</div>',
            //$dateTime->i18nFormat('HH:mm'),
            '<div><span class="time">%1$s</span>%2$s</div>',
            $this->timePanel($dateTime),
            $descript,
        );

        return $ret;
    }

    /**
     * Output line with time and description
     *
     * @param \App\Model\Entity\User $user User
     * @return string
     */
    public function userIcon(User $user): string
    {
        $icon = 'user_reader.png';
        if ($user->privileges <= 10) {
            $icon = 'user_writer.png';
        }
        if ($user->privileges <= 7) {
            $icon = 'user_groupadmin.png';
        }
        if ($user->privileges <= 5) {
            $icon = 'user_admin.png';
        }
        if ($user->privileges <= 2) {
            $icon = 'user_root.png';
        }

        $ret = $this->Html->image($icon);

        return $ret;
    }

    /**
     * Output line with search panel
     *
     * @param string $defaultValue Default Value
     * @return string
     */
    public function searchPanel(string $defaultValue): string
    {
        return sprintf(
            '<div class="search-panel"><input type="text" placeholder ="%1$s" value="%2$s"></div>',
            __('Search'),
            $defaultValue,
        );
    }
}
