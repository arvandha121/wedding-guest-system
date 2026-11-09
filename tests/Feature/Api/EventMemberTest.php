<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserRole;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class EventMemberTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    public function test_super_admin_can_assign_event_admin(): void
    {
        $event = Event::factory()->create();
        $target = User::factory()->eventAdmin()->create();

        $this->asUser(User::factory()->superAdmin()->create())
            ->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $target->ulid])
            ->assertCreated();

        $this->assertDatabaseHas('event_user', [
            'event_id' => $event->id, 'user_id' => $target->id, 'assigned_role' => 'event_admin',
        ]);
    }

    public function test_event_admin_can_assign_receptionist_but_not_admin(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);
        $receptionist = User::factory()->receptionist()->create();
        $otherAdmin = User::factory()->eventAdmin()->create();

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $receptionist->ulid])
            ->assertCreated();

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $otherAdmin->ulid])
            ->assertStatus(403);

        $this->assertDatabaseMissing('event_user', ['event_id' => $event->id, 'user_id' => $otherAdmin->id]);
    }

    public function test_super_admin_accounts_and_inactive_users_cannot_be_assigned(): void
    {
        $event = Event::factory()->create();
        $super = User::factory()->superAdmin()->create();
        $inactive = User::factory()->receptionist()->inactive()->create();

        $this->asUser($super)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $super->ulid])
            ->assertStatus(422)->assertJsonPath('code', 'USER_NOT_ASSIGNABLE');

        $this->asUser($super)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $inactive->ulid])
            ->assertStatus(422)->assertJsonPath('code', 'USER_NOT_ASSIGNABLE');
    }

    public function test_duplicate_assignment_and_unknown_user_are_rejected(): void
    {
        $event = Event::factory()->create();
        $super = User::factory()->superAdmin()->create();
        $target = User::factory()->receptionist()->create();
        $this->assign($event, $target);

        $this->asUser($super)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $target->ulid])
            ->assertStatus(409)->assertJsonPath('code', 'ALREADY_ASSIGNED');

        $this->asUser($super)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => '01ARZ3NDEKTSV4RRFFQ69G5FAV'])
            ->assertStatus(422)->assertJsonValidationErrors(['user']);
    }

    public function test_members_can_be_listed_and_filtered_by_role(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create(['name' => 'Admin Acara']);
        $receptionist = User::factory()->receptionist()->create(['name' => 'Resepsionis Satu']);
        $this->assign($event, $admin);
        $this->assign($event, $receptionist);

        $this->asUser($admin)->getJson("/api/v1/events/{$event->ulid}/members")
            ->assertOk()->assertJsonCount(2, 'data');

        $this->asUser($admin)->getJson("/api/v1/events/{$event->ulid}/members?role=receptionist")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Resepsionis Satu')
            ->assertJsonPath('data.0.assigned_role', 'receptionist')
            ->assertJsonMissingPath('data.0.password');

        $this->asUser($receptionist)->getJson("/api/v1/events/{$event->ulid}/members")->assertStatus(403);
    }

    public function test_event_admin_can_remove_receptionist_but_not_admin(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $other = User::factory()->eventAdmin()->create();
        $receptionist = User::factory()->receptionist()->create();
        $this->assign($event, $admin);
        $this->assign($event, $other);
        $this->assign($event, $receptionist);

        $this->asUser($admin)->deleteJson("/api/v1/events/{$event->ulid}/members/{$receptionist->ulid}")->assertOk();
        $this->assertDatabaseMissing('event_user', ['event_id' => $event->id, 'user_id' => $receptionist->id]);

        $this->asUser($admin)->deleteJson("/api/v1/events/{$event->ulid}/members/{$other->ulid}")->assertStatus(403);
        $this->assertDatabaseHas('event_user', ['event_id' => $event->id, 'user_id' => $other->id]);

        $this->asUser($admin)->deleteJson("/api/v1/events/{$event->ulid}/members/{$receptionist->ulid}")
            ->assertStatus(404)->assertJsonPath('code', 'NOT_ASSIGNED');
    }

    public function test_event_admin_can_create_receptionist_account_for_event(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/receptionists", [
            'name' => 'Rina Resepsionis',
            'email' => 'Rina@Contoh.com',
            'password' => 'Sandi-Kuat-123',
        ])->assertCreated()->assertJsonPath('data.email', 'rina@contoh.com');

        $user = User::query()->where('email', 'rina@contoh.com')->firstOrFail();
        $this->assertSame(UserRole::Receptionist, $user->role);
        $this->assertTrue($user->isActive());
        $this->assertTrue($event->hasMember($user, UserRole::Receptionist));
    }

    public function test_receptionist_creation_validates_email_and_password(): void
    {
        $event = Event::factory()->create();
        $admin = User::factory()->eventAdmin()->create();
        $this->assign($event, $admin);
        User::factory()->create(['email' => 'ada@contoh.com']);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/receptionists", [
            'name' => 'Dobel', 'email' => 'ADA@contoh.com', 'password' => 'Sandi-Kuat-123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->asUser($admin)->postJson("/api/v1/events/{$event->ulid}/receptionists", [
            'name' => 'Lemah', 'email' => 'baru@contoh.com', 'password' => 'pendek',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'baru@contoh.com']);
    }

    public function test_unassigned_event_admin_cannot_touch_members(): void
    {
        $event = Event::factory()->create();
        $stranger = User::factory()->eventAdmin()->create();
        $target = User::factory()->receptionist()->create();

        $this->asUser($stranger)->getJson("/api/v1/events/{$event->ulid}/members")->assertStatus(404);
        $this->asUser($stranger)->postJson("/api/v1/events/{$event->ulid}/members", ['user' => $target->ulid])->assertStatus(404);
        $this->asUser($stranger)->postJson("/api/v1/events/{$event->ulid}/receptionists", [
            'name' => 'X', 'email' => 'x@contoh.com', 'password' => 'Sandi-Kuat-123',
        ])->assertStatus(404);
    }
}
