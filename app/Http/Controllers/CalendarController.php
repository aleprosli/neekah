<?php

namespace App\Http\Controllers;

use App\Models\WeddingSite;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class CalendarController extends Controller
{
    /**
     * A calendar file guests can add to their phone in one tap.
     */
    public function __invoke(string $subdomain): Response
    {
        $site = WeddingSite::query()->published()->where('subdomain', $subdomain)->firstOrFail();

        $start = $site->event_date->copy()->setTimeFromTimeString($site->starts_at ?? '11:00:00');
        $end = $site->event_date->copy()->setTimeFromTimeString($site->ends_at ?? '16:00:00');

        if ($end->lessThanOrEqualTo($start)) {
            $end = $start->copy()->addHours(4);
        }

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Neekah//Kad Jemputan//MS',
            'BEGIN:VEVENT',
            'UID:'.$site->subdomain.'@'.config('neekah.site_domain'),
            'DTSTAMP:'.Carbon::now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$end->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escape('Majlis Perkahwinan '.$site->coupleNames()),
            'LOCATION:'.$this->escape(trim($site->venue_name.' '.$site->venue_address)),
            'DESCRIPTION:'.$this->escape($site->url()),
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="'.$site->subdomain.'.ics"',
        ]);
    }

    private function escape(string $value): string
    {
        return str_replace([',', ';', "\n"], ['\,', '\;', '\n'], trim($value));
    }
}
