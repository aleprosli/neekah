<?php

namespace App\Actions;

use App\Models\VendorBookingSetting;
use App\Models\VendorUnavailableDate;
use App\Support\IcalUrlGuard;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Throwable;

/**
 * Bring a vendor's busy days in from their Google Calendar's secret iCal
 * address, so a booking taken on WhatsApp and put in their own calendar
 * closes the date here too.
 *
 * Imported days are rows with source "ical": each import replaces them, and
 * never touches a date the vendor closed by hand. All-day events end the day
 * before DTEND (it is exclusive); timed events close every Malaysian date
 * they touch. Free (TRANSPARENT) and cancelled events are ignored.
 */
class ImportVendorIcal
{
    /** Anything larger is not a personal calendar. */
    public const MAX_BYTES = 2 * 1024 * 1024;

    /** Failed imports in a row before the vendor is told. */
    public const FAILURES_BEFORE_NOTICE = 3;

    public function __construct(private IcalUrlGuard $guard) {}

    /**
     * @return int dates closed by the calendar after this import
     *
     * @throws RuntimeException when the calendar cannot be fetched or read
     */
    public function handle(VendorBookingSetting $settings): int
    {
        $tz = new DateTimeZone(config('app.timezone'));
        $from = CarbonImmutable::today($tz);
        $to = $from->addMonthsNoOverflow(max(1, $settings->max_advance_months))->endOfDay();

        $busy = $this->busyDays($this->read($this->fetch((string) $settings->ical_url)), $from, $to, $tz);
        $vendor = $settings->vendor;
        $capacity = max(1, $settings->max_per_day);

        DB::transaction(function () use ($vendor, $busy, $from, $to, $capacity): void {
            // Matched on the calendar date in PHP: how a date column stores its
            // value differs between databases, so a string comparison is not.
            $existing = $vendor->unavailableDates()
                ->where('source', VendorUnavailableDate::SOURCE_ICAL)
                ->whereDate('date', '>=', $from->toDateString())
                ->whereDate('date', '<=', $to->toDateString())
                ->get()
                ->keyBy(fn (VendorUnavailableDate $row): string => $row->date->toDateString());

            $stale = $existing->reject(fn (VendorUnavailableDate $row, string $date): bool => isset($busy[$date]));
            $vendor->unavailableDates()->whereKey($stale->modelKeys())->delete();

            foreach ($busy as $date => $events) {
                // One busy event closes a one-team vendor's day; with more
                // teams each event takes a place, and a full day is closed.
                $slots = $capacity > 1 && count($events) < $capacity ? count($events) : null;
                $row = $existing->get($date) ?? $vendor->unavailableDates()->make(['date' => $date, 'source' => VendorUnavailableDate::SOURCE_ICAL]);

                $row->fill(['slots' => $slots, 'reason' => mb_substr(implode(', ', array_unique(array_filter($events))), 0, 120) ?: null])->save();
            }
        });

        $settings->update(['ical_synced_at' => now(), 'ical_error' => null, 'ical_failures' => 0]);

        return count($busy);
    }

    private function fetch(string $url): string
    {
        try {
            $target = $this->guard->check($url);
        } catch (Throwable $exception) {
            throw new RuntimeException('ical_url_refused', previous: $exception);
        }

        $response = Http::timeout(10)
            ->accept('text/calendar')
            ->withOptions([
                'allow_redirects' => false,
                // Connect to the address the guard approved, not a fresh lookup.
                'curl' => [CURLOPT_RESOLVE => ["{$target['host']}:443:{$target['ip']}"]],
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('ical_unreachable');
        }

        $body = $response->body();

        if (strlen($body) > self::MAX_BYTES) {
            throw new RuntimeException('ical_too_large');
        }

        return $body;
    }

    private function read(string $body): VCalendar
    {
        try {
            $calendar = Reader::read($body, Reader::OPTION_FORGIVING);
        } catch (Throwable $exception) {
            throw new RuntimeException('ical_unreadable', previous: $exception);
        }

        if (! $calendar instanceof VCalendar) {
            throw new RuntimeException('ical_unreadable');
        }

        return $calendar;
    }

    /**
     * Every Malaysian date in the window with the titles of what is on it.
     *
     * @return array<string, list<string>>
     */
    private function busyDays(VCalendar $calendar, CarbonImmutable $from, CarbonImmutable $to, DateTimeZone $tz): array
    {
        $expanded = $calendar->expand($from, $to, $tz);
        $busy = [];

        foreach ($expanded->select('VEVENT') as $event) {
            /** @var VEvent $event */
            if (! isset($event->DTSTART)
                || strtoupper((string) ($event->TRANSP ?? '')) === 'TRANSPARENT'
                || strtoupper((string) ($event->STATUS ?? '')) === 'CANCELLED') {
                continue;
            }

            $title = trim((string) ($event->SUMMARY ?? ''));

            foreach ($this->datesOf($event, $tz) as $date) {
                if ($date >= $from->toDateString() && $date <= $to->toDateString()) {
                    $busy[$date][] = $title;
                }
            }
        }

        ksort($busy);

        return $busy;
    }

    /**
     * @return list<string>
     */
    private function datesOf(VEvent $event, DateTimeZone $tz): array
    {
        $allDay = ! $event->DTSTART->hasTime();
        $start = CarbonImmutable::instance($event->DTSTART->getDateTime($tz))->setTimezone($tz);

        if (isset($event->DTEND)) {
            $end = CarbonImmutable::instance($event->DTEND->getDateTime($tz))->setTimezone($tz);
        } elseif (isset($event->DURATION)) {
            $end = $start->add($event->DURATION->getDateInterval());
        } else {
            $end = $allDay ? $start->addDay() : $start;
        }

        // DTEND is exclusive for all-day events, and a timed event ending at
        // midnight does not touch the next day.
        $last = ($allDay || ($end->greaterThan($start) && $end->format('H:i:s') === '00:00:00')) ? $end->subDay() : $end;

        if ($allDay) {
            $start = CarbonImmutable::parse($start->toDateString(), $tz);
            $last = CarbonImmutable::parse($last->toDateString(), $tz);
        }

        $dates = [];

        for ($day = $start->startOfDay(); $day->lte($last) && count($dates) < 400; $day = $day->addDay()) {
            $dates[] = $day->toDateString();
        }

        return $dates === [] ? [$start->toDateString()] : $dates;
    }
}
