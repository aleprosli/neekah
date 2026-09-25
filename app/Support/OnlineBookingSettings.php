<?php

namespace App\Support;

/**
 * Online booking across the whole site (Admin → Tetapan → Tempahan online):
 * the switch that opens it at all, how long an unpaid booking holds its date,
 * and how recently a vendor must have confirmed their calendar. Off by default,
 * so a deploy changes nothing until an admin opens it.
 */
class OnlineBookingSettings extends SettingGroup
{
    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled');
    }

    public function holdHours(): int
    {
        return max(1, min(72, (int) $this->value('hold_hours')));
    }

    public function calendarFreshDays(): int
    {
        return max(1, min(60, (int) $this->value('calendar_fresh_days')));
    }

    /**
     * @return array<string, int|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'hold_hours' => 24,
            'calendar_fresh_days' => 14,
        ];
    }

    protected static function prefix(): string
    {
        return 'online_booking';
    }
}
