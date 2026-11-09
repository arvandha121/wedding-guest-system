<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\Event;
use App\Models\EventSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class EventPagesTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private function eventForm(array $override = []): array
    {
        return array_merge([
            'name' => 'Pernikahan Dewi & Raka',
            'title' => 'The Wedding of Dewi & Raka',
            'groom_name' => 'Raka',
            'bride_name' => 'Dewi',
            'event_date' => now()->addDays(60)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '14:00',
            'venue_name' => 'Balai Kota',
            'venue_address' => 'Jl. Pahlawan 10',
            'checkin_mode' => 'single',
            'allow_manual_checkin' => '1',
        ], $override);
    }

    public function test_super_admin_sees_all_events_and_event_admin_only_assigned(): void
    {
        $mine = Event::factory()->create(['name' => 'Acara Milik Saya']);
        Event::factory()->create(['name' => 'Acara Rahasia Lain']);
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($mine, $admin);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.events.index'))
            ->assertOk()->assertSee('Acara Milik Saya')->assertSee('Acara Rahasia Lain');

        $this->actingAs($admin)
            ->get(route('admin.events.index'))
            ->assertOk()->assertSee('Acara Milik Saya')->assertDontSee('Acara Rahasia Lain');
    }

    public function test_only_super_admin_can_open_create_form(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.events.create'))->assertOk();
        $this->actingAs(User::factory()->eventAdmin()->create())->get(route('admin.events.create'))->assertForbidden();
    }

    public function test_super_admin_can_create_event_through_form(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.events.store'), $this->eventForm())
            ->assertSessionHasNoErrors();

        $event = Event::query()->where('name', 'Pernikahan Dewi & Raka')->firstOrFail();

        $this->assertSame('draft', $event->status->value);
    }

    public function test_create_form_shows_validation_errors(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->from(route('admin.events.create'))
            ->post(route('admin.events.store'), $this->eventForm(['name' => '', 'google_maps_url' => 'https://contoh.com']))
            ->assertRedirect(route('admin.events.create'))
            ->assertSessionHasErrors(['name', 'google_maps_url']);
    }

    public function test_event_detail_shows_sessions_and_assignments(): void
    {
        $event = Event::factory()->create(['name' => 'Acara Detail']);
        EventSession::factory()->create(['event_id' => $event->id, 'name' => 'Akad Pagi']);
        $admin = User::factory()->eventAdmin()->create(['name' => 'Pak Admin']);
        $receptionist = User::factory()->receptionist()->create(['name' => 'Bu Resepsionis']);
        $this->assign($event, $admin);
        $this->assign($event, $receptionist);

        $this->actingAs($admin)->get(route('admin.events.show', $event))
            ->assertOk()
            ->assertSee('Acara Detail')
            ->assertSee('Akad Pagi')
            ->assertSee('Pak Admin')
            ->assertSee('Bu Resepsionis');
    }

    public function test_unassigned_event_admin_gets_404_on_event_pages(): void
    {
        $event = Event::factory()->create();
        $stranger = User::factory()->eventAdmin()->create();

        $this->actingAs($stranger)->get(route('admin.events.show', $event))->assertNotFound();
        $this->actingAs($stranger)->get(route('admin.events.edit', $event))->assertNotFound();
    }

    public function test_session_can_be_added_through_form(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);

        $this->actingAs($admin)->post(route('admin.sessions.store', $event), [
            'name' => 'Resepsi Siang',
            'start_time' => '2026-12-20T12:00',
            'end_time' => '2026-12-20T15:00',
        ])->assertRedirect(route('admin.events.show', $event));

        $this->assertDatabaseHas('event_sessions', ['event_id' => $event->id, 'name' => 'Resepsi Siang']);
    }

    public function test_publishing_without_session_shows_error_message(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->from(route('admin.events.show', $event))
            ->patch(route('admin.events.status', $event), ['status' => 'published'])
            ->assertRedirect(route('admin.events.show', $event))
            ->assertSessionHas('error');

        $this->assertSame('draft', $event->fresh()->status->value);
    }

    public function test_receptionist_dashboard_lists_only_assigned_events(): void
    {
        $mine = Event::factory()->create(['name' => 'Acara Tugas Saya']);
        Event::factory()->create(['name' => 'Acara Bukan Tugas']);
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($mine, $receptionist);

        $this->actingAs($receptionist)->get(route('receptionist.dashboard'))
            ->assertOk()->assertSee('Acara Tugas Saya')->assertDontSee('Acara Bukan Tugas');
    }

    public function test_receptionist_cannot_open_event_pages(): void
    {
        $event = Event::factory()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $receptionist);

        $this->actingAs($receptionist)->get(route('admin.events.index'))->assertForbidden();
        $this->actingAs($receptionist)->get(route('admin.events.show', $event))->assertForbidden();
    }

    public function test_user_pages_are_for_super_admin_only(): void
    {
        $this->actingAs(User::factory()->eventAdmin()->create())->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs(User::factory()->superAdmin()->create())->get(route('admin.users.index'))->assertOk();
    }

    public function test_super_admin_can_create_user_through_form(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Admin Baru',
                'email' => 'adminbaru@contoh.com',
                'password' => 'Sandi-Kuat-123',
                'role' => 'event_admin',
            ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', ['email' => 'adminbaru@contoh.com', 'role' => 'event_admin']);
    }
}
