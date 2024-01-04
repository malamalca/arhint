<?php
use Kigkonsult\Icalcreator\Vcalendar;

$vcalendar = Vcalendar::factory([Vcalendar::UNIQUE_ID => 'intranet.arhim.si'])
    ->setMethod(Vcalendar::PUBLISH)
    ->setXprop(Vcalendar::X_WR_CALNAME, h($this->getCurrentUser()->name) . ' Calendar')
    ->setXprop(Vcalendar::X_WR_CALDESC, 'intranet.arhim.si user calendar')
    ->setXprop(Vcalendar::X_WR_RELCALID, $this->getCurrentUser()->id)
    ->setXprop(Vcalendar::X_WR_TIMEZONE, 'Europe/Ljubljana');


foreach ($events as $event) {
    $event1 = $vcalendar->newVevent()
        ->setUid($event->id)
        ->setTransp(Vcalendar::OPAQUE)
        ->setClass(Vcalendar::P_BLIC)
        ->setSequence(1)
        ->setSummary($event->title);

    if ($event->all_day) {
        $event1->setDtstart($event->dat_start->format('Ymd'));
        $event1->setDtend($event->dat_start->addDays(1)->format('Ymd'));
    } else {
        $event1->setDtstart($event->dat_start);
        $event1->setDtend($event->dat_end);
    }
}

$vcalendarString =
    $vcalendar->vtimezonePopulate()
    ->createCalendar();

header('Content-Type: text/calendar');
echo $vcalendarString;
die;