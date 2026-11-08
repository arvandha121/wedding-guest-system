<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_values_lists_all_roles(): void
    {
        $this->assertSame(['super_admin', 'event_admin', 'receptionist'], UserRole::values());
    }

    public function test_only_receptionist_is_not_admin(): void
    {
        $this->assertTrue(UserRole::SuperAdmin->isAdmin());
        $this->assertTrue(UserRole::EventAdmin->isAdmin());
        $this->assertFalse(UserRole::Receptionist->isAdmin());
    }

    public function test_labels_are_in_indonesian(): void
    {
        $this->assertSame('Resepsionis', UserRole::Receptionist->label());
    }
}
