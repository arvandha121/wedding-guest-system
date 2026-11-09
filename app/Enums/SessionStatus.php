<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionStatus: string
{
    case Scheduled = 'scheduled';
    case Open = 'open';
    case Closed = 'closed';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Dijadwalkan',
            self::Open => 'Dibuka',
            self::Closed => 'Ditutup',
        };
    }
}
