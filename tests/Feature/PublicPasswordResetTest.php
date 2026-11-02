<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PublicPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_uses_generic_response_and_sends_mail_for_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'donor@example.test', 'role' => 'user']);

        $this->post(route('public.password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status_message', 'Jika email terdaftar, tautan reset password akan dikirim.');

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_reset_password_updates_password_and_allows_login(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.test', 'password' => 'old-password', 'role' => 'user']);
        $token = Password::broker()->createToken($user);

        $this->post(route('public.password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('public.login'));

        $this->post(route('public.login.store'), ['email' => $user->email, 'password' => 'new-password-123'])
            ->assertRedirect(route('public.account'));
    }
}
