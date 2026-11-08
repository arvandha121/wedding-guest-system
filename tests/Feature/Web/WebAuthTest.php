<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $state = 'superAdmin'): User
    {
        return User::factory()->{$state}()->create([
            'email' => 'web@example.com',
            'password' => Hash::make('Secret-pass-123'),
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_login_page_is_displayed(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_user_can_login_and_is_sent_to_dashboard(): void
    {
        $user = $this->user();

        $this->post('/login', ['email' => 'web@example.com', 'password' => 'Secret-pass-123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        $this->user();

        $this->from('/login')
            ->post('/login', ['email' => 'web@example.com', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->user();
        $user->forceFill(['status' => UserStatus::Inactive])->save();

        $this->from('/login')
            ->post('/login', ['email' => 'web@example.com', 'password' => 'Secret-pass-123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_ends_session(): void
    {
        $this->actingAs($this->user())->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_dashboard_redirects_by_role(): void
    {
        $this->actingAs(User::factory()->eventAdmin()->create())
            ->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $this->actingAs(User::factory()->receptionist()->create())
            ->get('/dashboard')->assertRedirect(route('receptionist.dashboard'));
    }

    public function test_admin_dashboard_shows_account_info(): void
    {
        $user = User::factory()->superAdmin()->create(['name' => 'Alya Pratama']);

        $this->actingAs($user)->get('/admin')
            ->assertOk()
            ->assertSee('Alya Pratama')
            ->assertSee('Super Admin');
    }

    public function test_receptionist_cannot_open_admin_area(): void
    {
        $this->actingAs(User::factory()->receptionist()->create())->get('/admin')->assertForbidden();
    }

    public function test_event_admin_cannot_open_receptionist_area(): void
    {
        $this->actingAs(User::factory()->eventAdmin()->create())->get('/receptionist')->assertForbidden();
    }

    public function test_deactivated_user_with_active_session_is_logged_out(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $user->forceFill(['status' => UserStatus::Inactive])->save();

        $this->get('/admin')->assertRedirect(route('login'));
    }
}
