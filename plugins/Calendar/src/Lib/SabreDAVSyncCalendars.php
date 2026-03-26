<?php
declare(strict_types=1);

namespace Calendar\Lib;

use Cake\I18n\DateTime as CakeDateTime;
use Cake\ORM\TableRegistry;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Exception;
use Sabre\CalDAV;
use Sabre\CalDAV\Backend\AbstractBackend;
use Sabre\CalDAV\Backend\SyncSupport;
use Sabre\DAV\PropPatch;
use Sabre\VObject;

/**
 * SabreDAV CalDAV backend for the Calendar plugin.
 *
 * Maps Calendar\Model\Entity\Event entities to/from iCalendar VEVENT objects.
 * The calendar ID maps to a user's ID; events are stored with calendar_id = user_id.
 */
class SabreDAVSyncCalendars extends AbstractBackend implements SyncSupport
{
    /**
     * Returns the list of calendars for a specific user.
     *
     * @param string $principalUri Principal uri (e.g. principals/username)
     * @return array
     */
    public function getCalendarsForUser($principalUri): array
    {
        $parts = explode('/', $principalUri);
        $username = array_pop($parts);

        $Users = TableRegistry::getTableLocator()->get('Users');
        $user = $Users->find()
            ->select()
            ->where(['username' => $username])
            ->first();

        if (!$user) {
            return [];
        }

        return [
            [
                'id' => $user->id,
                'uri' => 'default',
                'principaluri' => 'principals/' . $user->username,
                '{DAV:}displayname' => 'Calendar',
                '{' . CalDAV\Plugin::NS_CALDAV . '}calendar-description' => 'Personal Calendar',
                '{' . CalDAV\Plugin::NS_CALDAV . '}supported-calendar-component-set'
                    => new CalDAV\Xml\Property\SupportedCalendarComponentSet(['VEVENT']),
            ],
        ];
    }

    /**
     * Updates properties for a calendar. No-op.
     *
     * @param mixed $calendarId
     * @param \Sabre\DAV\PropPatch $propPatch
     */
    public function updateCalendar($calendarId, PropPatch $propPatch): void
    {
    }

    /**
     * Creates a new calendar for a principal. Not supported.
     *
     * @param string $principalUri
     * @param string $calendarUri
     * @param array $properties
     * @return mixed
     */
    public function createCalendar($principalUri, $calendarUri, array $properties): mixed
    {
        return false;
    }

    /**
     * Deletes a calendar and all its objects. Not supported; no-op.
     *
     * @param mixed $calendarId
     */
    public function deleteCalendar($calendarId): void
    {
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * @param mixed $calendarId User id used as calendar identifier.
     * @return array
     */
    public function getCalendarObjects($calendarId): array
    {
        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $events = $EventsTable->find()
            ->select()
            ->where(['calendar_id' => $calendarId])
            ->all();

        $results = [];
        foreach ($events as $event) {
            $icalData = $this->eventToVCalendar($event)->serialize();
            $results[] = [
                'id' => $event->id,
                'uri' => $event->id . '.ics',
                'lastmodified' => (int)$event->modified->setTimezone('UTC')->toUnixString(),
                'etag' => '"' . md5($event->id . $event->modified->setTimezone('UTC')->toUnixString()) . '"',
                'size' => strlen($icalData),
                'component' => 'vevent',
            ];
        }

        return $results;
    }

    /**
     * Returns a single calendar object, or null if not found.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri): ?array
    {
        $id = $this->uriToId($objectUri);

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $event = $EventsTable->find()
            ->select()
            ->where(['id' => $id, 'calendar_id' => $calendarId])
            ->first();

        if (!$event) {
            return null;
        }

        $icalData = $this->eventToVCalendar($event)->serialize();

        return [
            'id' => $event->id,
            'uri' => $event->id . '.ics',
            'lastmodified' => (int)$event->modified->setTimezone('UTC')->toUnixString(),
            'etag' => '"' . md5($event->id . $event->modified->setTimezone('UTC')->toUnixString()) . '"',
            'size' => strlen($icalData),
            'calendardata' => $icalData,
            'component' => 'vevent',
        ];
    }

    /**
     * Returns a list of calendar objects by URI.
     *
     * @param mixed $calendarId
     * @param array $uris
     * @return array
     */
    public function getMultipleCalendarObjects($calendarId, array $uris): array
    {
        $ids = array_map([$this, 'uriToId'], $uris);

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $events = $EventsTable->find()
            ->select()
            ->where(['id IN' => $ids, 'calendar_id' => $calendarId])
            ->all();

        $results = [];
        foreach ($events as $event) {
            $icalData = $this->eventToVCalendar($event)->serialize();
            $results[] = [
                'id' => $event->id,
                'uri' => $event->id . '.ics',
                'lastmodified' => (int)$event->modified->setTimezone('UTC')->toUnixString(),
                'etag' => '"' . md5($event->id . $event->modified->setTimezone('UTC')->toUnixString()) . '"',
                'size' => strlen($icalData),
                'calendardata' => $icalData,
                'component' => 'vevent',
            ];
        }

        return $results;
    }

    /**
     * Creates a new calendar object from iCalendar data.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null ETag on success, null on failure.
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        $vCalendar = VObject\Reader::read($calendarData, VObject\Reader::OPTION_FORGIVING);

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        /** @var \Calendar\Model\Entity\Event $event */
        $event = $EventsTable->newEmptyEntity();
        $event->id = $this->uriToId($objectUri);
        $event->calendar_id = $calendarId;
        $event->user_id = $calendarId;

        $this->applyVEventToEntity($vCalendar->VEVENT, $event);

        if ($EventsTable->save($event)) {
            return '"' . md5($event->id . $event->modified->setTimezone('UTC')->toUnixString()) . '"';
        }

        return null;
    }

    /**
     * Updates an existing calendar object.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     * @param string $calendarData
     * @return string|null ETag on success, null on failure.
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData): ?string
    {
        $vCalendar = VObject\Reader::read($calendarData, VObject\Reader::OPTION_FORGIVING);

        $id = $this->uriToId($objectUri);

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        /** @var \Calendar\Model\Entity\Event|null $event */
        $event = $EventsTable->find()
            ->where(['id' => $id, 'calendar_id' => $calendarId])
            ->first();

        if (!$event) {
            return null;
        }

        $this->applyVEventToEntity($vCalendar->VEVENT, $event);

        if ($EventsTable->save($event)) {
            return '"' . md5($event->id . $event->modified->setTimezone('UTC')->toUnixString()) . '"';
        }

        return null;
    }

    /**
     * Deletes an existing calendar object.
     *
     * @param mixed $calendarId
     * @param string $objectUri
     */
    public function deleteCalendarObject($calendarId, $objectUri): void
    {
        $id = $this->uriToId($objectUri);

        $EventsTable = TableRegistry::getTableLocator()->get('Calendar.Events');
        $event = $EventsTable->find()
            ->where(['id' => $id, 'calendar_id' => $calendarId])
            ->first();

        if ($event) {
            $EventsTable->delete($event);
        }
    }

    /**
     * Returns changes since the given sync token. Returns null since detailed
     * change tracking is not implemented; clients will fall back to a full sync.
     *
     * @param mixed $calendarId
     * @param string|null $syncToken
     * @param int $syncLevel
     * @param int|null $limit
     * @return array|null
     */
    public function getChangesForCalendar($calendarId, $syncToken, $syncLevel, $limit = null): ?array
    {
        return null;
    }

    /**
     * Strips the .ics extension from a CalDAV object URI to obtain the event ID.
     *
     * @param string $uri
     * @return string
     */
    private function uriToId(string $uri): string
    {
        return preg_replace('/\.ics$/i', '', $uri);
    }

    /**
     * Converts a Calendar\Model\Entity\Event to a VCALENDAR with a VEVENT component.
     *
     * @param \Calendar\Model\Entity\Event $event
     * @return \Sabre\VObject\Component\VCalendar
     */
    private function eventToVCalendar($event): VObject\Component\VCalendar
    {
        $vCalendar = new VObject\Component\VCalendar();
        $vEvent = $vCalendar->createComponent('VEVENT');

        $vEvent->UID = $event->id;
        $vEvent->SUMMARY = $event->title ?? '';
        $vEvent->DTSTAMP = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        if (!empty($event->location)) {
            $vEvent->LOCATION = $event->location;
        }

        if (!empty($event->body)) {
            $vEvent->DESCRIPTION = $event->body;
        }

        if ($event->all_day) {
            $startStr = $event->dat_start->format('Ymd');
            // DTEND for all-day events is exclusive (the day after the last event day)
            $endStr = $event->dat_end->toNative()->modify('+1 day')->format('Ymd');
            $vEvent->add('DTSTART', $startStr, ['VALUE' => 'DATE']);
            $vEvent->add('DTEND', $endStr, ['VALUE' => 'DATE']);
        } else {
            $dtStart = $event->dat_start->toNative()->setTimezone(new DateTimeZone('UTC'));
            $dtEnd = $event->dat_end->toNative()->setTimezone(new DateTimeZone('UTC'));
            $vEvent->DTSTART = $dtStart;
            $vEvent->DTEND = $dtEnd;
        }

        if ($event->reminder !== null && $event->reminder > 0) {
            $vAlarm = $vCalendar->createComponent('VALARM');
            $vAlarm->add('ACTION', 'DISPLAY');
            $vAlarm->add('DESCRIPTION', 'Reminder');
            $vAlarm->add('TRIGGER', '-PT' . $event->reminder . 'M');
            $vEvent->add($vAlarm);
        }

        $vCalendar->add($vEvent);

        return $vCalendar;
    }

    /**
     * Applies data from a VEVENT component to a Calendar\Model\Entity\Event entity.
     *
     * @param \Sabre\VObject\Component\VEvent $vEvent
     * @param \Calendar\Model\Entity\Event $event
     */
    private function applyVEventToEntity($vEvent, $event): void
    {
        $event->title = isset($vEvent->SUMMARY) ? (string)$vEvent->SUMMARY : '';
        $event->location = isset($vEvent->LOCATION) ? (string)$vEvent->LOCATION : null;
        $event->body = isset($vEvent->DESCRIPTION) ? (string)$vEvent->DESCRIPTION : null;

        $allDay = isset($vEvent->DTSTART) && !$vEvent->DTSTART->hasTime();
        $event->all_day = $allDay;

        if (isset($vEvent->DTSTART)) {
            if ($allDay) {
                // Parse DATE value directly to avoid floating-time timezone issues
                $event->dat_start = CakeDateTime::parse($vEvent->DTSTART->getValue());
            } else {
                $event->dat_start = CakeDateTime::createFromTimestamp(
                    $vEvent->DTSTART->getDateTime()->getTimestamp(),
                );
            }
        }

        if (isset($vEvent->DTEND)) {
            if ($allDay) {
                // DTEND for all-day events is exclusive; subtract 1 day for storage
                $endDate = new DateTime($vEvent->DTEND->getValue());
                $endDate->modify('-1 day');
                $event->dat_end = CakeDateTime::parse($endDate->format('Y-m-d'));
            } else {
                $event->dat_end = CakeDateTime::createFromTimestamp(
                    $vEvent->DTEND->getDateTime()->getTimestamp(),
                );
            }
        } elseif (isset($vEvent->DURATION) && isset($vEvent->DTSTART)) {
            // Derive DTEND from DTSTART + DURATION
            try {
                $start = DateTime::createFromImmutable($vEvent->DTSTART->getDateTime());
                $start->add(new DateInterval((string)$vEvent->DURATION));
                $event->dat_end = CakeDateTime::createFromTimestamp($start->getTimestamp());
            } catch (Exception $e) {
                // leave dat_end unset if duration is unparseable
            }
        }

        // Parse the first VALARM TRIGGER as a reminder in minutes
        $event->reminder = null;
        if (isset($vEvent->VALARM)) {
            foreach ($vEvent->VALARM as $alarm) {
                if (isset($alarm->TRIGGER)) {
                    try {
                        $triggerStr = ltrim((string)$alarm->TRIGGER, '-');
                        if (str_starts_with($triggerStr, 'P')) {
                            $interval = new DateInterval($triggerStr);
                            $minutes = $interval->d * 24 * 60 + $interval->h * 60 + $interval->i;
                            if ($minutes > 0) {
                                $event->reminder = $minutes;
                            }
                        }
                    } catch (Exception $e) {
                        // ignore unparseable trigger
                    }
                }
                break; // only the first alarm
            }
        }
    }
}
