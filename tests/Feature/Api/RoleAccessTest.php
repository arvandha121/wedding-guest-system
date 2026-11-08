<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private const USERS = '/api/v1/users';

    private function tokenFor(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    public function test_super_admin_can_list_users_without_sensitive_fields(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(3)->receptionist()->create();

        $this->withToken($this->tokenFor($admin))->getJson(self::USERS)
            ->assertOk()
            ->assertJsonPath('meta.total', 4)
            ->assertJsonMissingPath('data.0.password')
            ->assertJsonMissingPath('data.0.remember_token')
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'status']]]);
    }

    public function test_user_ids_in_response_are_ulids_not_database_ids(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $id = $this->withToken($this->tokenFor($admin))->getJson(self::USERS)->json('data.0.id');

        $this->assertIsString($id);
        $this->assertSame(26, strlen($id));
    }

    public function test_event_admin_is_forbidden(): void
    {
        $user = User::factory()->eventAdmin()->create();

        $this->withToken($this->tokenFor($user))->getJson(self::USERS)
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_receptionist_is_forbidden(): void
    {
        $user = User::factory()->receptionist()->create();

        $this->withToken($this->tokenFor($user))->getJson(self::USERS)
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_guest_is_unauthenticated(): void
    {
        $this->getJson(self::USERS)->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_list_can_be_filtered_by_role_and_search(): void
    {
        $admin = User::factory()->superAdmin()->create(['name' => 'Alya Admin']);
        User::factory()->receptionist()->create(['name' => 'Budi Resepsionis']);
        User::factory()->eventAdmin()->create(['name' => 'Citra Event']);
        $token = $this->tokenFor($admin);

        $this->withToken($token)->getJson(self::USERS . '?role=receptionist')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Budi Resepsionis');

        $this->withToken($token)->getJson(self::USERS . '?search=citra')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'event_admin');
    }

    public function test_invalid_filter_is_rejected(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->withToken($this->tokenFor($admin))->getJson(self::USERS . '?role=hacker&per_page=1000')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['role', 'per_page']);
    }

    public function test_unknown_api_route_returns_json_404(): void
    {
        $this->getJson('/api/v1/tidak-ada')
            ->assertStatus(404)
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}
