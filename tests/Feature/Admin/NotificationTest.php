<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Notifications\AdminActionRequired;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_center_requires_admin_permission(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/notifications')->assertForbidden();
    }

    public function test_admin_can_view_and_mark_notifications_read(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->notify(new AdminActionRequired([
            'kind' => 'test',
            'title' => 'Review diperlukan',
            'message' => 'Contoh notifikasi.',
            'url' => '/admin',
        ]));

        $notification = $admin->unreadNotifications()->firstOrFail();

        $this->actingAs($admin)->get('/admin/notifications')
            ->assertOk()
            ->assertSee('Review diperlukan')
            ->assertSee('Contoh notifikasi.');

        $this->actingAs($admin)->post('/admin/notifications/'.$notification->id.'/read')
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_admin_cannot_mark_another_users_notification_read(): void
    {
        $owner = User::factory()->create(['role' => 'admin']);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $owner->notify(new AdminActionRequired(['kind' => 'test', 'title' => 'Private notification']));
        $notification = $owner->unreadNotifications()->firstOrFail();

        $this->actingAs($otherAdmin)
            ->post('/admin/notifications/'.$notification->id.'/read')
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }
}
