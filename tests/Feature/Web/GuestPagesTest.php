<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\Guest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class GuestPagesTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    public function test_assigned_event_admin_sees_guest_list_and_stranger_gets_404(): void
    {
        $event = Event::factory()->create();
        Guest::factory()->create(['event_id' => $event->id, 'name' => 'Tamu Terlihat']);
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);

        $this->actingAs($admin)->get(route('admin.events.guests.index', $event))->assertOk()->assertSee('Tamu Terlihat');
        $this->actingAs(User::factory()->eventAdmin()->create())->get(route('admin.events.guests.index', $event))->assertNotFound();
    }

    public function test_guest_can_be_created_and_deleted_through_pages(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.events.guests.store', $event), ['name' => 'Tamu Baru', 'quota' => 2])
            ->assertRedirect(route('admin.events.guests.index', $event));

        $guest = Guest::query()->where('name', 'Tamu Baru')->firstOrFail();

        $this->actingAs($admin)->delete(route('admin.guests.destroy', $guest))->assertRedirect();
        $this->assertSoftDeleted('guests', ['id' => $guest->id]);
    }

    public function test_receptionist_cannot_open_guest_pages(): void
    {
        $event = Event::factory()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $receptionist);

        $this->actingAs($receptionist)->get(route('admin.events.guests.index', $event))->assertForbidden();
    }
}
