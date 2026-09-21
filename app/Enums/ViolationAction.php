<?php

namespace App\Enums;

/**
 * The escalation ladder from the kertas kerja: warning, point deduction with a
 * ranking drop, temporary suspension, then removal for repeat offenders.
 */
enum ViolationAction: string
{
    case Warning = 'warning';
    case PointDeduction = 'point_deduction';
    case Suspension = 'suspension';
    case Removal = 'removal';

    public function label(): string
    {
        return match ($this) {
            self::Warning => __('enums.violation_action.warning'),
            self::PointDeduction => __('enums.violation_action.point_deduction'),
            self::Suspension => __('enums.violation_action.suspension'),
            self::Removal => __('enums.violation_action.removal'),
        };
    }

    /**
     * The action that applies to a vendor's nth upheld violation.
     */
    public static function forOffence(int $offenceNumber): self
    {
        return match (true) {
            $offenceNumber <= 1 => self::Warning,
            $offenceNumber === 2 => self::PointDeduction,
            $offenceNumber === 3 => self::Suspension,
            default => self::Removal,
        };
    }

    public function penaltyPoints(): int
    {
        return match ($this) {
            self::Warning => 0,
            self::PointDeduction => 100,
            self::Suspension => 200,
            self::Removal => 300,
        };
    }
}
