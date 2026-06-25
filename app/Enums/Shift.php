<?php

namespace App\Enums;

enum Shift: string
{
    case Day = 'D';
    case Night = 'N';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Day',
            self::Night => 'Night',
        };
    }

    public static function shiftRange(Shift $shift): array
    {
        return match ($shift) {
            self::Day => ['08:00:00', '20:00:00'],
            self::Night => ['20:00:00', '08:00:00'],
        };
    }
}
