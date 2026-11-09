<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class EventTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'Pernikahan Andi & Sari',
            'title' => 'The Wedding of Andi & Sari',
            'groom_name' => 'Andi',
            'bride_name' => 'Sari',
            'event_date' => now()->addDays(40)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '13:00',
            'venue_name' => 'Gedung Serbaguna',
            'venue_address' => 'Jl. Merdeka No. 1, Surabaya',
            'google_maps_url' => 'https://maps.app.goo.gl/abc123',
            'checkin_mode' => 'group',
        ], $override);
    }

    public function test_super_admin_can_create_event(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->postJson('/api/v1/events', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Pernikahan Andi & Sari')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.checkin_mode', 'group')
            ->assertJsonPath('data.start_time', '09:00')
            ->assertJsonPath('data.theme', 'classic');

        $this->assertDatabaseHas('events', ['name' => 'Pernikahan Andi & Sari', 'created_by' => $admin->id]);
    }

    public function test_create_validates_required_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->postJson('/api/v1/events', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([
                'name', 'title', 'groom_name', 'bride_name', 'event_date',
                'start_time', 'end_time', 'venue_name', 'venue_address',
            ]);
    }

    public function test_create_rejects_non_google_maps_url_and_bad_checkin_window(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->postJson('/api/v1/events', $this->payload([
            'google_maps_url' => 'https://contoh.com/peta',
            'checkin_opens_at' => '2026-12-20T10:00',
            'checkin_closes_at' => '2026-12-20T09:00',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['google_maps_url', 'checkin_closes_at']);
    }

    public function test_only_super_admin_can_create_events(): void
    {
        foreach ([User::factory()->eventAdmin()->create(), User::factory()->receptionist()->create()] as $user) {
            $this->asUser($user)->postJson('/api/v1/events', $this->payload())
                ->assertStatus(403)
                ->assertJsonPath('code', 'FORBIDDEN');
        }

        $this->assertDatabaseCount('events', 0);
    }

    public function test_events_are_scoped_to_assignments(): void
    {
        $mine = Event::factory()->create(['name' => 'Acara Saya']);
        Event::factory()->create(['name' => 'Acara Orang Lain']);

        $eventAdmin = User::factory()->eventAdmin()->create();
        $receptionist = User::factory()->receptionist()->create();
        $stranger = User::factory()->eventAdmin()->create();
        $this->assign($mine, $eventAdmin);
        $this->assign($mine, $receptionist);

        $this->asUser(User::factory()->superAdmin()->create())->getJson('/api/v1/events')
            ->assertOk()->assertJsonPath('meta.total', 2);

        $this->asUser($eventAdmin)->getJson('/api/v1/events')
            ->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', 'Acara Saya');

        $this->asUser($receptionist)->getJson('/api/v1/events')
            ->assertOk()->assertJsonPath('meta.total', 1);

        $this->asUser($stranger)->getJson('/api/v1/events')
            ->assertOk()->assertJsonPath('meta.total', 0);
    }

    public function test_unassigned_event_admin_gets_404_not_403(): void
    {
        $event = Event::factory()->create();
        $stranger = User::factory()->eventAdmin()->create();

        $this->asUser($stranger)->getJson("/api/v1/events/{$event->ulid}")
            ->assertStatus(404)->assertJsonPath('code', 'NOT_FOUND');

        $this->asUser($stranger)->putJson("/api/v1/events/{$event->ulid}", $this->payload())
            ->assertStatus(404);
    }

    public function test_events_require_authentication(): void
    {
        $this->getJson('/api/v1/events')->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_receptionist_gets_brief_event_data(): void
    {
        $event = Event::factory()->create(['description' => 'Catatan internal', 'contact_phone' => '0812345678']);
        $receptionist = User::factory()->receptionist()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $receptionist);
        $this->assign($event, $admin);

        $this->asUser($receptionist)->getJson("/api/v1/events/{$event->ulid}")
            ->assertOk()
            ->assertJsonPath('data.name', $event->name)
            ->assertJsonMissingPath('data.description')
            ->assertJsonMissingPath('data.contact_phone');

        $this->asUser($admin)->getJson("/api/v1/events/{$event->ulid}")
            ->assertOk()
            ->assertJsonPath('data.description', 'Catatan internal')
            ->assertJsonPath('data.contact_phone', '0812345678');
    }

    public function test_assigned_event_admin_can_update_but_receptionist_cannot(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $admin);
        $this->assign($event, $receptionist);

        $this->asUser($admin)->putJson("/api/v1/events/{$event->ulid}", $this->payload(['name' => 'Nama Baru']))
            ->assertOk()->assertJsonPath('data.name', 'Nama Baru');

        $this->assertDatabaseHas('events', ['id' => $event->id, 'name' => 'Nama Baru']);

        $this->asUser($receptionist)->putJson("/api/v1/events/{$event->ulid}", $this->payload())
            ->assertStatus(403);
    }

    public function test_checkin_window_is_stored_in_utc(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->putJson("/api/v1/events/{$event->ulid}", $this->payload([
            'checkin_opens_at' => '2026-12-20T08:00',
            'checkin_closes_at' => '2026-12-20T18:00',
        ]))->assertOk()->assertJsonPath('data.checkin_opens_at', '2026-12-20T08:00:00+07:00');

        $this->assertSame('01:00', $event->fresh()->checkin_opens_at->utc()->format('H:i'));
    }

    public function test_status_transitions_are_validated(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $url = "/api/v1/events/{$event->ulid}/status";

        $this->asUser($admin)->patchJson($url, ['status' => 'completed'])
            ->assertStatus(422)->assertJsonPath('code', 'INVALID_STATUS_TRANSITION');

        $this->asUser($admin)->patchJson($url, ['status' => 'published'])
            ->assertStatus(422)->assertJsonPath('code', 'EVENT_NOT_READY');

        EventSession::factory()->create(['event_id' => $event->id]);

        $this->asUser($admin)->patchJson($url, ['status' => 'published'])
            ->assertOk()->assertJsonPath('data.status', 'published');

        $this->assertSame(EventStatus::Published, $event->fresh()->status);
    }

    public function test_only_super_admin_can_archive_and_restore(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);
        $super = User::factory()->superAdmin()->create();

        $this->asUser($admin)->deleteJson("/api/v1/events/{$event->ulid}")->assertStatus(403);

        $this->asUser($super)->deleteJson("/api/v1/events/{$event->ulid}")->assertOk();
        $this->assertSoftDeleted('events', ['id' => $event->id]);

        $this->asUser($super)->getJson('/api/v1/events')->assertJsonPath('meta.total', 0);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/restore")->assertStatus(404);

        $this->asUser($super)->postJson("/api/v1/events/{$event->ulid}/restore")
            ->assertOk()->assertJsonPath('data.id', $event->ulid);

        $this->assertNotSoftDeleted('events', ['id' => $event->id]);
    }

    public function test_index_supports_search_and_status_filter(): void
    {
        Event::factory()->create(['name' => 'Pernikahan Budi', 'groom_name' => 'Budi']);
        Event::factory()->published()->create(['name' => 'Pernikahan Candra', 'groom_name' => 'Candra']);
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->getJson('/api/v1/events?search=budi')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Pernikahan Budi');

        $this->asUser($admin)->getJson('/api/v1/events?status=published')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Pernikahan Candra');

        $this->asUser($admin)->getJson('/api/v1/events?status=ngawur&per_page=999')
            ->assertStatus(422)->assertJsonValidationErrors(['status', 'per_page']);
    }

    public function test_uploaded_images_are_converted_to_webp_and_resized(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('Ekstensi GD tanpa dukungan WebP.');
        }

        Storage::fake('public');
        $admin = User::factory()->superAdmin()->create();

        $response = $this->asUser($admin)->post('/api/v1/events', $this->payload([
            'banner' => UploadedFile::fake()->image('banner.jpg', 3000, 1200),
            'couple_photo' => UploadedFile::fake()->image('foto.png', 800, 800),
        ]), ['Accept' => 'application/json'])->assertCreated();

        $event = Event::query()->where('ulid', $response->json('data.id'))->firstOrFail();

        $this->assertStringEndsWith('.webp', $event->banner_path);
        $this->assertStringEndsWith('.webp', $event->couple_photo_path);
        Storage::disk('public')->assertExists([$event->banner_path, $event->couple_photo_path]);
        $this->assertSame('image/webp', Storage::disk('public')->mimeType($event->banner_path));

        [$width] = getimagesizefromstring(Storage::disk('public')->get($event->banner_path));
        $this->assertLessThanOrEqual(1920, $width);

        $this->asUser($admin)->putJson("/api/v1/events/{$event->ulid}", $this->payload(['remove_banner' => true]))
            ->assertOk();

        Storage::disk('public')->assertMissing($event->banner_path);
        $this->assertNull($event->fresh()->banner_path);
        Storage::disk('public')->assertExists($event->couple_photo_path);
    }

    public function test_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');
        $admin = User::factory()->superAdmin()->create();

        $this->asUser($admin)->post('/api/v1/events', $this->payload([
            'banner' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
        ]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['banner']);

        $this->assertDatabaseCount('events', 0);
    }
}
