<?php

declare(strict_types=1);

namespace App\Enums;

enum CheckinMode: string
{
    case Single = 'single';
    case Group = 'group';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Single => '1 QR = 1 check-in',
            self::Group => '1 QR = rombongan (kuota)',
        };
    }
}
