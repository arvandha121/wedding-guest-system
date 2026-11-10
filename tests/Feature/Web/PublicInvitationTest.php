<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Enums\EventStatus;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesInvitations;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class PublicInvitationTest extends TestCase
{
    use CreatesInvitations, InteractsWithApi, RefreshDatabase;

    public function test_guest_sees_own_invitation_and_it_is_marked_opened(): void
    {
        [$event] = $this->eventWithAdmin();
        $invitation = $this->invite($event);
        $token = app(InvitationService::class)->publicToken($invitation);

        $this->get("/i/{$token}")
            ->assertOk()
            ->assertSee($invitation->guest->name)
            ->assertSee($event->venue_name)
            ->assertSee($invitation->invitation_code)
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        $this->assertSame('opened', $invitation->fresh()->status->value);
        $this->assertNotNull($invitation->fresh()->opened_at);
    }

    public function test_unknown_token_and_draft_event_are_404(): void
    {
        $this->get('/i/token-ngawur-yang-tidak-ada')->assertNotFound();

        [$draft] = $this->eventWithAdmin(EventStatus::Draft);
        $token = app(InvitationService::class)->publicToken($this->invite($draft));

        $this->get("/i/{$token}")->assertNotFound();
    }

    public function test_revoked_invitation_shows_notice_without_qr(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        $invitation = $this->invite($event);
        $token = app(InvitationService::class)->publicToken($invitation);
        app(InvitationService::class)->revoke($invitation, $admin, 'Dibatalkan');

        $this->get("/i/{$token}")->assertStatus(410)->assertSee('tidak lagi berlaku')->assertDontSee('qr.webp');
        $this->get("/i/{$token}/qr.webp")->assertStatus(410);
    }

    public function test_public_qr_image_is_webp(): void
    {
        $this->skipWithoutQr();
        [$event] = $this->eventWithAdmin();
        $token = app(InvitationService::class)->publicToken($this->invite($event));

        $this->get("/i/{$token}/qr.webp")->assertOk()->assertHeader('Content-Type', 'image/webp');
        $this->get("/i/{$token}/qr.webp?download=1")->assertOk()->assertHeader('Content-Disposition', 'attachment; filename="qr-undangan.webp"');
    }

    public function test_admin_pages_list_generate_and_preview(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        \App\Models\Guest::factory()->create(['event_id' => $event->id, 'name' => 'Tamu Dari Web']);

        $this->actingAs($admin)->post(route('admin.events.invitations.store', $event), ['all' => '1'])->assertRedirect();
        $this->assertDatabaseCount('invitations', 1);

        $this->actingAs($admin)->get(route('admin.events.invitations.index', $event))->assertOk()->assertSee('Tamu Dari Web');

        $invitation = \App\Models\Invitation::query()->firstOrFail();
        $this->actingAs($admin)->get(route('admin.invitations.preview', $invitation))->assertOk()->assertSee('Pratinjau admin');
        $this->assertSame('generated', $invitation->fresh()->status->value);
    }

    public function test_admin_invitation_pages_are_scoped(): void
    {
        [$event] = $this->eventWithAdmin();
        $invitation = $this->invite($event);
        $stranger = User::factory()->eventAdmin()->create();

        $this->actingAs($stranger)->get(route('admin.events.invitations.index', $event))->assertNotFound();
        $this->actingAs($stranger)->get(route('admin.invitations.preview', $invitation))->assertNotFound();
    }
}
