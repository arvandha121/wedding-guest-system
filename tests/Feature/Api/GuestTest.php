<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class GuestTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private function adminFor(Event $event): User
    {
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);

        return $admin;
    }

    private function category(Event $event, string $name = 'VIP'): GuestCategory
    {
        $category = new GuestCategory(['name' => $name]);
        $category->event_id = $event->id;
        $category->save();

        return $category;
    }

    public function test_guest_is_created_with_normalized_fields_and_category(): void
    {
        $event = Event::factory()->create();
        $category = $this->category($event);

        $this->asUser($this->adminFor($event))->postJson("/api/v1/events/{$event->ulid}/guests", [
            'name' => 'Budi Santoso', 'phone' => '0812-3456-7890', 'quota' => 4, 'category' => $category->ulid,
        ])
            ->assertCreated()
            ->assertJsonPath('data.quota', 4)
            ->assertJsonPath('data.category.name', 'VIP')
            ->assertJsonMissingPath('meta.duplicates');

        $this->assertDatabaseHas('guests', [
            'event_id' => $event->id, 'name_normalized' => 'budi santoso', 'phone_normalized' => '6281234567890',
        ]);
    }

    public function test_duplicate_name_and_phone_gives_warning_but_is_still_saved(): void
    {
        $event = Event::factory()->create();
        $admin = $this->adminFor($event);
        $url = "/api/v1/events/{$event->ulid}/guests";

        $this->asUser($admin)->postJson($url, ['name' => 'Siti Aminah', 'phone' => '081234567890'])->assertCreated();

        $this->asUser($admin)->postJson($url, ['name' => 'siti  aminah', 'phone' => '+6281234567890'])
            ->assertCreated()->assertJsonCount(1, 'meta.duplicates');

        $this->asUser($admin)->postJson($url, ['name' => 'Siti Aminah', 'phone' => '089999999999'])
            ->assertCreated()->assertJsonMissingPath('meta.duplicates');

        $this->assertDatabaseCount('guests', 3);
    }

    public function test_guest_validation(): void
    {
        $event = Event::factory()->create();
        $foreign = $this->category(Event::factory()->create());

        $this->asUser($this->adminFor($event))->postJson("/api/v1/events/{$event->ulid}/guests", [
            'name' => '', 'quota' => 0, 'email' => 'bukan-email', 'category' => $foreign->ulid,
        ])->assertStatus(422)->assertJsonValidationErrors(['name', 'quota', 'email', 'category']);
    }

    public function test_access_is_limited_to_assigned_event_admins(): void
    {
        $event = Event::factory()->create();
        $guest = Guest::factory()->create(['event_id' => $event->id]);
        $stranger = User::factory()->eventAdmin()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $receptionist);

        $this->asUser($stranger)->getJson("/api/v1/events/{$event->ulid}/guests")->assertStatus(404);
        $this->asUser($stranger)->getJson("/api/v1/guests/{$guest->ulid}")->assertStatus(404);
        $this->asUser($stranger)->deleteJson("/api/v1/guests/{$guest->ulid}")->assertStatus(404);

        $this->asUser($receptionist)->getJson("/api/v1/events/{$event->ulid}/guests")->assertStatus(403);
        $this->asUser($receptionist)->postJson("/api/v1/events/{$event->ulid}/guests", ['name' => 'X'])->assertStatus(403);
    }

    public function test_list_supports_search_by_name_and_phone_plus_category_filter(): void
    {
        $event = Event::factory()->create();
        $vip = $this->category($event);
        Guest::factory()->create(['event_id' => $event->id, 'name' => 'Andi Wijaya', 'phone' => '081111111111', 'category_id' => $vip->id]);
        Guest::factory()->create(['event_id' => $event->id, 'name' => 'Budi Hartono', 'phone' => '082222222222']);
        Guest::factory()->create(['name' => 'Tamu Acara Lain']);
        $admin = $this->adminFor($event);
        $base = "/api/v1/events/{$event->ulid}/guests";

        $this->asUser($admin)->getJson($base)->assertOk()->assertJsonPath('meta.total', 2);
        $this->asUser($admin)->getJson("{$base}?search=wijaya")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Andi Wijaya');
        $this->asUser($admin)->getJson("{$base}?search=0822222")->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Budi Hartono');
        $this->asUser($admin)->getJson("{$base}?category={$vip->ulid}")->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_guest_can_be_updated_and_soft_deleted(): void
    {
        $event = Event::factory()->create();
        $category = $this->category($event);
        $guest = Guest::factory()->create(['event_id' => $event->id, 'category_id' => $category->id]);
        $admin = $this->adminFor($event);

        $this->asUser($admin)->putJson("/api/v1/guests/{$guest->ulid}", ['name' => 'Nama Baru', 'quota' => 3, 'category' => null])
            ->assertOk()->assertJsonPath('data.name', 'Nama Baru')->assertJsonPath('data.category', null);

        $this->assertNull($guest->fresh()->category_id);

        $this->asUser($admin)->deleteJson("/api/v1/guests/{$guest->ulid}")->assertOk();
        $this->assertSoftDeleted('guests', ['id' => $guest->id]);
    }

    public function test_bulk_actions_only_touch_guests_of_this_event(): void
    {
        $event = Event::factory()->create();
        $category = $this->category($event);
        $mine = Guest::factory()->count(2)->create(['event_id' => $event->id]);
        $other = Guest::factory()->create();
        $admin = $this->adminFor($event);
        $ids = [...$mine->pluck('ulid')->all(), $other->ulid];

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/guests/bulk-category", ['ids' => $ids, 'category' => $category->ulid])
            ->assertOk()->assertJsonPath('data.affected', 2);
        $this->assertNull($other->fresh()->category_id);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/guests/bulk-category", ['ids' => $ids, 'category' => null])->assertOk();
        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/guests/bulk-category", ['ids' => $ids])->assertStatus(422);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/guests/bulk-delete", ['ids' => $ids])
            ->assertOk()->assertJsonPath('data.affected', 2);
        $this->assertNotSoftDeleted('guests', ['id' => $other->id]);
    }

    public function test_default_categories_are_seeded_when_event_is_created(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->asUser($admin)->postJson('/api/v1/events', [
            'name' => 'Acara Baru', 'title' => 'Judul', 'groom_name' => 'A', 'bride_name' => 'B',
            'event_date' => now()->addDays(10)->toDateString(), 'start_time' => '09:00', 'end_time' => '12:00',
            'venue_name' => 'Gedung', 'venue_address' => 'Jl. Contoh 1',
        ])->assertCreated();

        $event = Event::query()->where('ulid', $response->json('data.id'))->firstOrFail();

        $this->assertSame(count(config('wgs.default_guest_categories')), GuestCategory::query()->where('event_id', $event->id)->count());
        $this->asUser($admin)->getJson("/api/v1/events/{$event->ulid}/categories")->assertOk()->assertJsonFragment(['name' => 'VIP']);
    }

    public function test_category_crud_and_in_use_protection(): void
    {
        $event = Event::factory()->create();
        $admin = $this->adminFor($event);
        $url = "/api/v1/events/{$event->ulid}/categories";

        $created = $this->asUser($admin)->postJson($url, ['name' => 'Alumni'])->assertCreated()->assertJsonPath('data.is_default', false);
        $this->asUser($admin)->postJson($url, ['name' => 'Alumni'])->assertStatus(422)->assertJsonValidationErrors(['name']);

        $id = $created->json('data.id');
        $this->asUser($admin)->putJson("/api/v1/categories/{$id}", ['name' => 'Alumni SMA'])->assertOk()->assertJsonPath('data.name', 'Alumni SMA');

        Guest::factory()->create(['event_id' => $event->id, 'category_id' => GuestCategory::query()->where('ulid', $id)->value('id')]);
        $this->asUser($admin)->deleteJson("/api/v1/categories/{$id}")->assertStatus(409)->assertJsonPath('code', 'CATEGORY_IN_USE');

        $empty = $this->asUser($admin)->postJson($url, ['name' => 'Kosong'])->json('data.id');
        $this->asUser($admin)->deleteJson("/api/v1/categories/{$empty}")->assertOk();

        $this->asUser($this->adminFor(Event::factory()->create()))->putJson("/api/v1/categories/{$id}", ['name' => 'X'])->assertStatus(404);
    }
}
