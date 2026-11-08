<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case EventAdmin = 'event_admin';
    case Receptionist = 'receptionist';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::EventAdmin => 'Event Admin',
            self::Receptionist => 'Resepsionis',
        };
    }

    public function isAdmin(): bool
    {
        return $this !== self::Receptionist;
    }

    public function tokenTtlMinutes(): int
    {
        return (int) config('wgs.token_ttl_minutes.' . $this->value);
    }
}
