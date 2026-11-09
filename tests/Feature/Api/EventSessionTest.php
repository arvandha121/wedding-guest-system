<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class EventSessionTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private function body(array $override = []): array
    {
        return array_merge([
            'name' => 'Akad Nikah',
            'start_time' => '2026-12-20T09:00',
            'end_time' => '2026-12-20T11:00',
        ], $override);
    }

    public function test_assigned_event_admin_can_create_session_with_local_time_stored_as_utc(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);

        $response = $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/sessions", $this->body())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Akad Nikah')
            ->assertJsonPath('data.status', 'scheduled');

        $this->assertTrue(
            Carbon::parse($response->json('data.start_time'))->equalTo(Carbon::parse('2026-12-20T09:00', 'Asia/Jakarta'))
        );
        $this->assertSame('02:00', EventSession::query()->firstOrFail()->start_time->utc()->format('H:i'));
    }

    public function test_session_end_must_be_after_start(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/sessions", $this->body([
            'end_time' => '2026-12-20T08:00',
        ]))->assertStatus(422)->assertJsonValidationErrors(['end_time']);
    }

    public function test_sort_order_increments_and_list_is_ordered(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/sessions", $this->body(['name' => 'Akad']))->assertCreated();
        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/sessions", $this->body([
            'name' => 'Resepsi', 'start_time' => '2026-12-20T12:00', 'end_time' => '2026-12-20T15:00',
        ]))->assertCreated();

        $this->asUser($admin)->getJson("/api/v1/events/{$event->ulid}/sessions")
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Akad')
            ->assertJsonPath('data.0.sort_order', 0)
            ->assertJsonPath('data.1.name', 'Resepsi')
            ->assertJsonPath('data.1.sort_order', 1);
    }

    public function test_receptionist_can_view_but_not_manage_sessions(): void
    {
        $event = Event::factory()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $receptionist);
        $session = EventSession::factory()->create(['event_id' => $event->id]);

        $this->asUser($receptionist)->getJson("/api/v1/events/{$event->ulid}/sessions")->assertOk();

        $this->asUser($receptionist)->postJson("/api/v1/events/{$event->ulid}/sessions", $this->body())->assertStatus(403);
        $this->asUser($receptionist)->putJson("/api/v1/sessions/{$session->ulid}", $this->body())->assertStatus(403);
        $this->asUser($receptionist)->deleteJson("/api/v1/sessions/{$session->ulid}")->assertStatus(403);
    }

    public function test_sessions_of_other_events_are_not_found(): void
    {
        $event = Event::factory()->create();
        $session = EventSession::factory()->create(['event_id' => $event->id]);
        $stranger = User::factory()->eventAdmin()->create();

        $this->asUser($stranger)->getJson("/api/v1/events/{$event->ulid}/sessions")->assertStatus(404);
        $this->asUser($stranger)->postJson("/api/v1/events/{$event->ulid}/sessions", $this->body())->assertStatus(404);
        $this->asUser($stranger)->putJson("/api/v1/sessions/{$session->ulid}", $this->body())->assertStatus(404);
        $this->asUser($stranger)->deleteJson("/api/v1/sessions/{$session->ulid}")->assertStatus(404);
    }

    public function test_session_can_be_updated_and_deleted(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);
        $session = EventSession::factory()->create(['event_id' => $event->id]);

        $this->asUser($admin)->putJson("/api/v1/sessions/{$session->ulid}", $this->body([
            'name' => 'Resepsi Malam', 'status' => 'open',
        ]))->assertOk()->assertJsonPath('data.name', 'Resepsi Malam')->assertJsonPath('data.status', 'open');

        $this->asUser($admin)->deleteJson("/api/v1/sessions/{$session->ulid}")->assertOk();
        $this->assertDatabaseMissing('event_sessions', ['id' => $session->id]);
    }
}
