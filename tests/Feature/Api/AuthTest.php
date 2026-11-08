<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\UserStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private const LOGIN = '/api/v1/auth/login';
    private const ME = '/api/v1/auth/me';

    private function makeUser(string $state = 'eventAdmin', string $password = 'Secret-pass-123'): User
    {
        return User::factory()->{$state}()->create([
            'email' => 'Tester@Example.com',
            'password' => Hash::make($password),
        ]);
    }

    public function test_login_returns_token_and_safe_user_payload(): void
    {
        $user = $this->makeUser();

        $this->postJson(self::LOGIN, [
            'email' => 'tester@example.com',
            'password' => 'Secret-pass-123',
            'device_name' => 'Pixel',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.id', $user->ulid)
            ->assertJsonPath('data.user.role', 'event_admin')
            ->assertJsonMissingPath('data.user.password')
            ->assertJsonStructure([
                'success', 'message', 'meta',
                'data' => ['token', 'token_type', 'expires_at', 'user' => ['id', 'name', 'email', 'role', 'status']],
            ]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_email_is_case_insensitive(): void
    {
        $this->makeUser();

        $this->postJson(self::LOGIN, ['email' => 'TESTER@EXAMPLE.COM', 'password' => 'Secret-pass-123'])
            ->assertOk();
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->makeUser();

        $this->postJson(self::LOGIN, ['email' => 'tester@example.com', 'password' => 'salah'])
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_with_unknown_email_gives_same_response(): void
    {
        $this->postJson(self::LOGIN, ['email' => 'nobody@example.com', 'password' => 'whatever-123'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_CREDENTIALS')
            ->assertJsonPath('message', 'Email atau kata sandi salah.');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->makeUser();
        $user->forceFill(['status' => UserStatus::Inactive])->save();

        $this->postJson(self::LOGIN, ['email' => 'tester@example.com', 'password' => 'Secret-pass-123'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_validates_input(): void
    {
        $this->postJson(self::LOGIN, [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $payload = ['email' => 'tester@example.com', 'password' => 'salah'];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::LOGIN, $payload)->assertStatus(401);
        }

        $this->postJson(self::LOGIN, $payload)
            ->assertStatus(429)
            ->assertJsonPath('code', 'RATE_LIMITED');
    }

    public function test_token_lifetime_depends_on_role(): void
    {
        $this->makeUser('receptionist');

        $response = $this->postJson(self::LOGIN, ['email' => 'tester@example.com', 'password' => 'Secret-pass-123'])
            ->assertOk();

        $minutes = abs(now()->diffInMinutes(Carbon::parse($response->json('data.expires_at'))));

        $this->assertEqualsWithDelta(720, $minutes, 2);
    }

    public function test_me_returns_profile_for_valid_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->getJson(self::ME)
            ->assertOk()
            ->assertJsonPath('data.id', $user->ulid)
            ->assertJsonPath('data.email', 'tester@example.com');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson(self::ME)
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_me_rejects_invalid_token(): void
    {
        $this->withToken('999|bukan-token-valid')->getJson(self::ME)
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_expired_token_is_rejected(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson(self::ME)->assertStatus(401);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson(self::ME)->assertStatus(401);
    }

    public function test_refresh_rotates_token(): void
    {
        $user = $this->makeUser();
        $old = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($old)->postJson('/api/v1/auth/refresh')->assertOk();
        $new = $response->json('data.token');

        $this->assertNotSame($old, $new);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->app['auth']->forgetGuards();
        $this->withToken($old)->getJson(self::ME)->assertStatus(401);

        $this->app['auth']->forgetGuards();
        $this->withToken($new)->getJson(self::ME)->assertOk();
    }

    public function test_deactivated_user_loses_existing_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;
        $user->forceFill(['status' => UserStatus::Inactive])->save();

        $this->withToken($token)->getJson(self::ME)
            ->assertStatus(403)
            ->assertJsonPath('code', 'ACCOUNT_INACTIVE');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_health_endpoint_reports_database(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonPath('data.database', 'ok');
    }
}
