<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_super_admin_can_create_event_admin_and_receptionist(): void
    {
        $admin = $this->admin();

        foreach (['event_admin', 'receptionist'] as $i => $role) {
            $this->asUser($admin)->postJson('/api/v1/users', [
                'name' => "Pengguna {$i}",
                'email' => "user{$i}@contoh.com",
                'password' => 'Sandi-Kuat-123',
                'role' => $role,
            ])->assertCreated()->assertJsonPath('data.role', $role)->assertJsonMissingPath('data.password');
        }

        $this->assertDatabaseCount('users', 3);
    }

    public function test_super_admin_role_cannot_be_created_via_api(): void
    {
        $this->asUser($this->admin())->postJson('/api/v1/users', [
            'name' => 'Calon SA', 'email' => 'sa@contoh.com', 'password' => 'Sandi-Kuat-123', 'role' => 'super_admin',
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_email_must_be_unique_case_insensitively(): void
    {
        User::factory()->create(['email' => 'sama@contoh.com']);

        $this->asUser($this->admin())->postJson('/api/v1/users', [
            'name' => 'Dobel', 'email' => 'SAMA@contoh.com', 'password' => 'Sandi-Kuat-123', 'role' => 'receptionist',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_weak_password_is_rejected(): void
    {
        $this->asUser($this->admin())->postJson('/api/v1/users', [
            'name' => 'Lemah', 'email' => 'lemah@contoh.com', 'password' => 'abc', 'role' => 'receptionist',
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_be_updated_but_role_cannot_change(): void
    {
        $target = User::factory()->receptionist()->create(['email' => 'lama@contoh.com']);
        $admin = $this->admin();

        $this->asUser($admin)->putJson("/api/v1/users/{$target->ulid}", [
            'name' => 'Nama Baru', 'email' => 'baru@contoh.com', 'password' => 'Sandi-Baru-456',
        ])->assertOk()->assertJsonPath('data.name', 'Nama Baru');

        $fresh = $target->fresh();
        $this->assertSame('baru@contoh.com', $fresh->email);
        $this->assertTrue(Hash::check('Sandi-Baru-456', $fresh->password));

        $this->asUser($admin)->putJson("/api/v1/users/{$target->ulid}", [
            'name' => 'Nama Baru', 'email' => 'baru@contoh.com', 'role' => 'event_admin',
        ])->assertStatus(422)->assertJsonValidationErrors(['role']);
    }

    public function test_update_without_password_keeps_existing_password(): void
    {
        $target = User::factory()->receptionist()->create(['password' => Hash::make('Sandi-Lama-123')]);

        $this->asUser($this->admin())->putJson("/api/v1/users/{$target->ulid}", [
            'name' => 'Ganti Nama', 'email' => $target->email,
        ])->assertOk();

        $this->assertTrue(Hash::check('Sandi-Lama-123', $target->fresh()->password));
    }

    public function test_deactivating_user_revokes_tokens_and_blocks_login(): void
    {
        $target = User::factory()->receptionist()->create([
            'email' => 'resep@contoh.com', 'password' => Hash::make('Sandi-Kuat-123'),
        ]);
        $target->createToken('perangkat');
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->asUser($this->admin())->patchJson("/api/v1/users/{$target->ulid}/status", ['status' => 'inactive'])
            ->assertOk()->assertJsonPath('data.status', 'inactive');

        $this->assertSame(UserStatus::Inactive, $target->fresh()->status);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);

        $this->postJson('/api/v1/auth/login', ['email' => 'resep@contoh.com', 'password' => 'Sandi-Kuat-123'])
            ->assertStatus(403)->assertJsonPath('code', 'ACCOUNT_INACTIVE');
    }

    public function test_user_can_be_deleted_softly(): void
    {
        $target = User::factory()->eventAdmin()->create();
        $target->createToken('perangkat');

        $this->asUser($this->admin())->deleteJson("/api/v1/users/{$target->ulid}")->assertOk();

        $this->assertSoftDeleted('users', ['id' => $target->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $target->id]);
    }

    public function test_super_admin_accounts_are_protected(): void
    {
        $admin = $this->admin();
        $other = User::factory()->superAdmin()->create();

        $this->asUser($admin)->patchJson("/api/v1/users/{$other->ulid}/status", ['status' => 'inactive'])
            ->assertStatus(403)->assertJsonPath('code', 'PROTECTED_ACCOUNT');

        $this->asUser($admin)->deleteJson("/api/v1/users/{$other->ulid}")
            ->assertStatus(403)->assertJsonPath('code', 'PROTECTED_ACCOUNT');

        $this->asUser($admin)->putJson("/api/v1/users/{$other->ulid}", ['name' => 'X', 'email' => $other->email])
            ->assertStatus(403)->assertJsonPath('code', 'PROTECTED_ACCOUNT');
    }

    public function test_only_super_admin_can_manage_users(): void
    {
        $target = User::factory()->receptionist()->create();

        foreach ([User::factory()->eventAdmin()->create(), User::factory()->receptionist()->create()] as $user) {
            $this->asUser($user)->postJson('/api/v1/users', [])->assertStatus(403);
            $this->asUser($user)->getJson("/api/v1/users/{$target->ulid}")->assertStatus(403);
            $this->asUser($user)->deleteJson("/api/v1/users/{$target->ulid}")->assertStatus(403);
        }
    }
}
