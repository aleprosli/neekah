<?php

namespace App\Models;

use App\Enums\DepositType;
use App\Support\OnlineBookingSettings;
use Database\Factories\VendorBookingSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Pro vendor's online booking rules and the Herepay account their deposits
 * go to. One row per vendor, created the first time they save; until then
 * Vendor::bookingSettingsOrDefault() stands in with the defaults below.
 */
#[Fillable([
    'vendor_id', 'enabled', 'deposit_type', 'deposit_value', 'max_per_day', 'available_weekdays',
    'min_lead_days', 'max_advance_months', 'deposit_terms', 'manual_instructions',
    'herepay_secret_key', 'herepay_private_key', 'herepay_connected_at', 'herepay_verified_at',
    'calendar_confirmed_at', 'ical_url', 'ical_synced_at', 'ical_error', 'ical_failures',
])]
#[Hidden(['herepay_secret_key', 'herepay_private_key', 'ical_url'])]
class VendorBookingSetting extends Model
{
    /** @use HasFactory<VendorBookingSettingFactory> */
    use HasFactory;

    /** ISO weekdays, Monday 1 to Sunday 7. */
    public const ALL_WEEKDAYS = [1, 2, 3, 4, 5, 6, 7];

    public const MAX_PER_DAY = 20;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'enabled' => false,
        'deposit_type' => 'percent',
        'deposit_value' => 30,
        'max_per_day' => 1,
        'min_lead_days' => 14,
        'max_advance_months' => 18,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'deposit_type' => DepositType::class,
            'deposit_value' => 'decimal:2',
            'max_per_day' => 'integer',
            'available_weekdays' => 'array',
            'min_lead_days' => 'integer',
            'max_advance_months' => 'integer',
            'ical_failures' => 'integer',
            'herepay_secret_key' => 'encrypted',
            'herepay_private_key' => 'encrypted',
            'ical_url' => 'encrypted',
            'herepay_connected_at' => 'datetime',
            'herepay_verified_at' => 'datetime',
            'calendar_confirmed_at' => 'datetime',
            'ical_synced_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return list<int>
     */
    public function weekdays(): array
    {
        $days = array_values(array_intersect(self::ALL_WEEKDAYS, array_map('intval', $this->available_weekdays ?? self::ALL_WEEKDAYS)));

        return $days === [] ? self::ALL_WEEKDAYS : $days;
    }

    public function depositFor(float $price): float
    {
        return $this->deposit_type->amountFor($price, (float) $this->deposit_value);
    }

    public function hasHerepay(): bool
    {
        return filled($this->herepay_secret_key) && filled($this->herepay_private_key);
    }

    public function hasManualInstructions(): bool
    {
        return filled($this->manual_instructions);
    }

    /**
     * Confirmed recently enough that its dates can be trusted: couples book
     * straight from it, so a calendar nobody has looked at is not offered.
     */
    public function calendarIsFresh(?int $days = null): bool
    {
        $days ??= app(OnlineBookingSettings::class)->calendarFreshDays();

        // A vendor whose Google Calendar imported in the last day keeps it
        // current without tapping anything.
        if ($this->ical_synced_at !== null && $this->ical_synced_at->greaterThan(now()->subDay())) {
            return true;
        }

        return $this->calendar_confirmed_at !== null
            && $this->calendar_confirmed_at->greaterThan(now()->subDays($days));
    }

    /** The feed address with its secret part hidden, for showing back to the vendor. */
    public function maskedIcalUrl(): ?string
    {
        if (blank($this->ical_url)) {
            return null;
        }

        $parts = parse_url($this->ical_url);

        return ($parts['host'] ?? '').'/…/'.basename($parts['path'] ?? '');
    }
}
