<?php

namespace App\Domain\Tracking;

enum Period: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function noun(): string
    {
        return match ($this) {
            self::Daily => 'day',
            self::Weekly => 'week',
            self::Monthly => 'month',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Daily => 'today',
            self::Weekly => 'this week',
            self::Monthly => 'this month',
        };
    }
}
