<?php

namespace App\Enums;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

enum ClosingPeriod: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Diario',
            self::Weekly => 'Semanal',
            self::Monthly => 'Mensual',
        };
    }

    /**
     * Get the first and last day of the period that contains the given date.
     * Weeks run from Monday to Sunday.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function range(CarbonInterface $date): array
    {
        $date = CarbonImmutable::instance($date);

        return match ($this) {
            self::Daily => [$date->startOfDay(), $date->endOfDay()],
            self::Weekly => [$date->startOfWeek(CarbonInterface::MONDAY), $date->endOfWeek(CarbonInterface::SUNDAY)],
            self::Monthly => [$date->startOfMonth(), $date->endOfMonth()],
        };
    }
}
