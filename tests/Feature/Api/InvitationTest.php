<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\EventStatus;
use App\Mail\InvitationMail;
use App\Models\EventSession;
use App\Models\Guest;
use App\Models\Invitation;
use App\Models\User;
use App\Services\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\CreatesInvitations;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use CreatesInvitations, InteractsWithApi, RefreshDatabase;

    public function test_generate_for_all_guests_is_idempotent_and_never_exposes_tokens(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        Guest::factory()->count(3)->create(['event_id' => $event->id, 'quota' => 4]);
        $url = "/api/v1/events/{$event->ulid}/invitations";

        $this->asUser($admin)->postJson($url, ['all' => true])
            ->assertCreated()->assertJsonPath('data.created', 3)->assertJsonPath('data.skipped', 0);

        $this->asUser($admin)->postJson($url, ['all' => true])
            ->assertCreated()->assertJsonPath('data.created', 0)->assertJsonPath('data.skipped', 3);

        $response = $this->asUser($admin)->getJson($url)->assertOk()->assertJsonPath('meta.total', 3);
        $response->assertJsonPath('data.0.quota', 4)->assertJsonCount(1, 'data.0.sessions');

        $body = $response->getContent();
        $this->assertStringNotContainsString('WGS-', $body);
        $this->assertStringNotContainsString('qr_token', $body);
        $this->assertDatabaseCount('invitation_sessions', 3);
    }

    public function test_only_hash_and_encrypted_copy_are_stored(): void
    {
        [$event] = $this->eventWithAdmin();
        $invitation = $this->invite($event);
        $plain = app(InvitationService::class)->qrToken($invitation);

        $row = Invitation::query()->findOrFail($invitation->id)->getAttributes();

        $this->assertSame(64, strlen($row['qr_token_hash']));
        $this->assertStringNotContainsString($plain, $row['qr_token_enc']);
        $this->assertStringNotContainsString($plain, $row['qr_token_hash']);
        $this->assertTrue(app(InvitationService::class)->findByQrToken($plain)?->is($invitation));
    }

    public function test_generate_requires_guests_or_all_and_at_least_one_session(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        Guest::factory()->create(['event_id' => $event->id]);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/invitations", ['all' => false])
            ->assertStatus(422)->assertJsonValidationErrors(['guests']);

        $empty = \App\Models\Event::factory()->create();
        Guest::factory()->create(['event_id' => $empty->id]);
        $this->assign($empty, $admin);

        $this->asUser($admin)->postJson("/api/v1/events/{$empty->ulid}/invitations", ['all' => true])
            ->assertStatus(422)->assertJsonPath('code', 'EVENT_HAS_NO_SESSIONS');
    }

    public function test_generate_for_selected_guests_and_sessions(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        $second = EventSession::factory()->create(['event_id' => $event->id, 'name' => 'Resepsi']);
        $chosen = Guest::factory()->create(['event_id' => $event->id]);
        Guest::factory()->create(['event_id' => $event->id]);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/invitations", [
            'guests' => [$chosen->ulid], 'sessions' => [$second->ulid],
        ])->assertCreated()->assertJsonPath('data.created', 1);

        $invitation = Invitation::query()->where('guest_id', $chosen->id)->firstOrFail();
        $this->assertSame([$second->id], $invitation->sessions()->pluck('event_sessions.id')->all());
        $this->assertDatabaseCount('invitations', 1);
    }

    public function test_regenerating_qr_kills_old_token_but_keeps_public_link(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        $invitation = $this->invite($event);
        $service = app(InvitationService::class);
        $oldQr = $service->qrToken($invitation);
        $publicToken = $service->publicToken($invitation);

        $this->asUser($admin)->postJson("/api/v1/invitations/{$invitation->ulid}/generate-qr")
            ->assertOk()->assertJsonPath('data.qr_version', 2);

        $this->assertNull($service->findByQrToken($oldQr));
        $this->assertTrue($service->findByQrToken($service->qrToken($invitation->fresh()))?->is($invitation));
        $this->assertTrue($service->findByPublicToken($publicToken)?->is($invitation));
    }

    public function test_revoked_invitation_cannot_be_changed_and_guest_can_be_reinvited(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        $invitation = $this->invite($event);

        $this->asUser($admin)->postJson("/api/v1/invitations/{$invitation->ulid}/revoke", [])->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->asUser($admin)->postJson("/api/v1/invitations/{$invitation->ulid}/revoke", ['reason' => 'Salah kirim'])
            ->assertOk()->assertJsonPath('data.status', 'revoked')->assertJsonPath('data.revoke_reason', 'Salah kirim')->assertJsonPath('data.public_url', null);

        $this->asUser($admin)->postJson("/api/v1/invitations/{$invitation->ulid}/generate-qr")
            ->assertStatus(422)->assertJsonPath('code', 'INVITATION_REVOKED');

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/invitations", ['all' => true])
            ->assertCreated()->assertJsonPath('data.created', 1);

        $this->assertSame(1, Invitation::query()->where('guest_id', $invitation->guest_id)->active()->count());
    }

    public function test_deleting_a_guest_revokes_their_invitation(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        $invitation = $this->invite($event);

        $this->asUser($admin)->deleteJson("/api/v1/guests/{$invitation->guest->ulid}")->assertOk();

        $this->assertSame('revoked', $invitation->fresh()->status->value);
    }

    public function test_session_used_by_invitation_cannot_be_deleted(): void
    {
        [$event, $admin] = $this->eventWithAdmin();
        $this->invite($event);
        $session = $event->sessions()->firstOrFail();

        $this->asUser($admin)->deleteJson("/api/v1/sessions/{$session->ulid}")
            ->assertStatus(409)->assertJsonPath('code', 'SESSION_IN_USE');
    }

    public function test_access_is_limited_to_event_admins_of_the_event(): void
    {
        [$event] = $this->eventWithAdmin();
        $invitation = $this->invite($event);
        $stranger = User::factory()->eventAdmin()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $receptionist);

        $this->asUser($stranger)->getJson("/api/v1/events/{$event->ulid}/invitations")->assertStatus(404);
        $this->asUser($stranger)->postJson("/api/v1/invitations/{$invitation->ulid}/revoke", ['reason' => 'x'])->assertStatus(404);
        $this->asUser($receptionist)->getJson("/api/v1/events/{$event->ulid}/invitations")->assertStatus(403);
        $this->asUser($receptionist)->getJson("/api/v1/invitations/{$invitation->ulid}/qr.webp")->assertStatus(403);
    }

    public function test_qr_image_is_lossless_webp(): void
    {
        $this->skipWithoutQr();
        [$event, $admin] = $this->eventWithAdmin();
        $invitation = $this->invite($event);

        $response = $this->asUser($admin)->get("/api/v1/invitations/{$invitation->ulid}/qr.webp")->assertOk();
        $body = $response->getContent();

        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        $this->assertSame('WEBP', substr($body, 8, 4));
        $this->assertSame('VP8L', substr($body, 12, 4), 'QR harus lossless');
    }

    public function test_email_requires_published_event_and_recipient(): void
    {
        Mail::fake();
        [$draft, $admin] = $this->eventWithAdmin(EventStatus::Draft);
        $invitation = $this->invite($draft);

        $this->asUser($admin)->postJson("/api/v1/invitations/{$invitation->ulid}/resend", ['channel' => 'email'])
            ->assertStatus(422)->assertJsonPath('code', 'EVENT_NOT_PUBLISHED');

        [$event, $admin2] = $this->eventWithAdmin();
        $noEmail = $this->invite($event, Guest::factory()->create(['event_id' => $event->id, 'email' => null]));

        $this->asUser($admin2)->postJson("/api/v1/invitations/{$noEmail->ulid}/resend", ['channel' => 'email'])
            ->assertStatus(422)->assertJsonPath('code', 'NO_RECIPIENT');

        Mail::assertNothingSent();
    }

    public function test_email_is_sent_logged_and_status_becomes_sent(): void
    {
        Mail::fake();
        [$event, $admin] = $this->eventWithAdmin();
        $invitation = $this->invite($event, Guest::factory()->create(['event_id' => $event->id, 'email' => 'tamu@contoh.com']));

        $this->asUser($admin)->postJson("/api/v1/invitations/{$invitation->ulid}/resend", ['channel' => 'email'])
            ->assertStatus(202);

        Mail::assertSent(InvitationMail::class, fn ($mail) => $mail->hasTo('tamu@contoh.com') && str_contains($mail->url, '/i/'));
        $this->assertSame('sent', $invitation->fresh()->status->value);

        $this->asUser($admin)->getJson("/api/v1/invitations/{$invitation->ulid}/deliveries")
            ->assertOk()->assertJsonPath('data.0.status', 'sent')->assertJsonPath('data.0.recipient', 'tamu@contoh.com');
    }

    public function test_bulk_send_skips_guests_without_email(): void
    {
        Mail::fake();
        [$event, $admin] = $this->eventWithAdmin();
        $this->invite($event, Guest::factory()->create(['event_id' => $event->id, 'email' => 'a@contoh.com']));
        $this->invite($event, Guest::factory()->create(['event_id' => $event->id, 'email' => null]));

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/invitations/send")
            ->assertStatus(202)->assertJsonPath('data.queued', 1)->assertJsonPath('data.skipped', 1);

        Mail::assertSent(InvitationMail::class, 1);
    }
}
