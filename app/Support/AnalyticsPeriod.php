<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsPeriod
{
    /**
     * Selectable windows, in months.
     *
     * @var array<int, string>
     */
    public const CHOICES = [3 => '3 bulan', 6 => '6 bulan', 12 => '12 bulan', 24 => '24 bulan'];

    private function __construct(
        public readonly int $months,
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $months = (int) $request->integer('months', 12);

        if (! array_key_exists($months, self::CHOICES)) {
            $months = 12;
        }

        return new self(
            $months,
            now()->startOfMonth()->subMonths($months - 1),
            now()->endOfMonth(),
        );
    }

    public function label(): string
    {
        return self::CHOICES[$this->months];
    }

    /**
     * Every month in the window, so a month with nothing in it draws as a gap
     * rather than being silently dropped from the chart.
     *
     * @return Collection<int, string>
     */
    public function months(): Collection
    {
        return collect(range(0, $this->months - 1))
            ->map(fn (int $offset): string => $this->start->copy()->addMonths($offset)->format('Y-m'));
    }

    /**
     * Turn "Y-m" => value pairs into a chart series covering the whole window.
     *
     * @param  Collection<string, mixed>|array<string, mixed>  $values
     * @return array<int, array{label: string, value: float}>
     */
    public function series(Collection|array $values): array
    {
        $values = collect($values);

        return $this->months()
            ->map(fn (string $month): array => [
                'label' => Carbon::createFromFormat('Y-m', $month)->translatedFormat('M y'),
                'value' => (float) ($values[$month] ?? 0),
            ])
            ->all();
    }
}
