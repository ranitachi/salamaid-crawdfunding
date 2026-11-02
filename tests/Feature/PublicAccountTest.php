<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Tests\TestCase;

class PublicAccountTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(): Campaign
    {
        $organization = Organization::create(['slug' => 'account-'.Str::random(8), 'name' => 'Account Organization', 'status' => 'verified', 'verified_at' => now()]);
        return Campaign::create(['organization_id' => $organization->id, 'title' => 'Account Campaign', 'slug' => 'account-campaign-'.Str::random(5), 'description' => 'Cerita', 'target_amount' => 1_000_000, 'status' => 'published']);
    }

    public function test_guest_is_redirected_to_public_login_and_can_register(): void
    {
        $this->get(route('public.account'))->assertRedirect(route('public.login'));
        $this->post(route('public.register.store'), ['name' => 'Public Donor', 'email' => 'public@example.test', 'password' => 'password-123', 'password_confirmation' => 'password-123'])->assertRedirect(route('public.account'));
        $this->assertAuthenticated()->assertDatabaseHas('users', ['email' => 'public@example.test', 'role' => 'user']);
    }

    public function test_donations_are_scoped_to_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $campaign = $this->campaign();
        Donation::create(['campaign_id' => $campaign->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);
        Donation::create(['campaign_id' => $campaign->id, 'user_id' => $other->id, 'gross_amount' => 90000, 'net_amount' => 90000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($owner)->get(route('public.donations'))->assertOk()->assertSee('Rp 50.000')->assertDontSee('Rp 90.000');
    }

    public function test_notification_read_action_is_scoped_to_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = DatabaseNotification::create(['id' => (string) Str::uuid(), 'type' => 'test', 'notifiable_type' => User::class, 'notifiable_id' => $other->id, 'data' => ['title' => 'Private'], 'read_at' => null]);

        $this->actingAs($owner)->post(route('public.inbox.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_receipt_is_not_accessible_to_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($other)->get(route('public.receipt', $donation))->assertNotFound();
        $this->actingAs($owner)->get(route('public.receipt', $donation))->assertOk()->assertSee('Receipt Salamaid');
    }
}
