<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\AdminActionRequired;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class AdminActionRequiredNotificationTest extends TestCase
{
    public function test_email_channel_is_opt_in(): void
    {
        $user = User::factory()->make(['email' => 'admin@example.test']);
        $notification = new AdminActionRequired(['title' => 'Review', 'message' => 'Perlu ditinjau.', 'url' => '/admin']);

        config(['admin.notifications.email_enabled' => false]);
        $this->assertSame(['database'], $notification->via($user));

        config(['admin.notifications.email_enabled' => true]);
        $this->assertSame(['database', 'mail'], $notification->via($user));
        $this->assertInstanceOf(MailMessage::class, $notification->toMail($user));
    }
}
