<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;

trait CreatesInvitations
{
    /** @return array{0: Event, 1: User} acara berstatus published dengan satu sesi, dan Event Admin-nya */
    protected function eventWithAdmin(EventStatus $status = EventStatus::Published): array
    {
        $event = Event::factory()->create(['status' => $status]);
        EventSession::factory()->create(['event_id' => $event->id]);
        $admin = User::factory()->eventAdmin()->create();
        $event->members()->attach($admin->id, ['assigned_role' => $admin->role->value]);

        return [$event, $admin];
    }

    protected function invite(Event $event, ?Guest $guest = null): Invitation
    {
        $guest ??= Guest::factory()->create(['event_id' => $event->id, 'email' => fake()->unique()->safeEmail()]);

        return app(InvitationService::class)->generate($event, $guest);
    }

    protected function skipWithoutQr(): void
    {
        if (! function_exists('imagewebp') || ! class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            $this->markTestSkipped('Butuh GD dengan WebP dan paket endroid/qr-code.');
        }
    }
}
