<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_login_page_is_shown(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('name="password"', false);
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->getJson('/admin')->assertUnauthorized();
    }

    public function test_user_with_role_user_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_user_with_unknown_role_is_forbidden(): void
    {
        $this->actingAs(User::factory()->role('hacker')->create())->get('/admin')->assertForbidden();
    }

    public function test_inactive_admin_is_logged_out_and_rejected(): void
    {
        $user = User::factory()->role('super_admin')->inactive()->create();

        $this->actingAs($user)->get('/admin')
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_every_admin_role_can_open_dashboard(): void
    {
        foreach (array_keys(config('admin.roles')) as $role) {
            $this->actingAs(User::factory()->role($role)->create())
                ->get('/admin')
                ->assertOk();
        }
    }

    public function test_admin_can_login_and_logout(): void
    {
        $user = User::factory()->role('reviewer')->create();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->get('/admin/login')->assertRedirect(route('admin.dashboard'));

        $this->post('/admin/logout')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }

    public function test_login_rejects_wrong_password_without_flashing_it(): void
    {
        $user = User::factory()->role('admin')->create();

        $this->from('/admin/login')
            ->post('/admin/login', ['email' => $user->email, 'password' => 'wrong-secret'])
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->assertGuest();
    }

    public function test_login_rejects_non_admin_and_inactive_users(): void
    {
        $donor = User::factory()->create();
        $inactive = User::factory()->role('finance')->inactive()->create();

        foreach ([$donor, $inactive] as $user) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
                ->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->role('admin')->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'wrong']);
        }

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
