<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Opened = 'opened';
    case Revoked = 'revoked';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Generated => 'Dibuat',
            self::Sent => 'Terkirim',
            self::Delivered => 'Diterima',
            self::Opened => 'Dibuka',
            self::Revoked => 'Dicabut',
        };
    }
}
