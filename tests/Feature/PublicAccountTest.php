<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Payment\Entities\Payment;
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

    public function test_inbox_tabs_filter_notifications_and_detail_marks_read(): void
    {
        $owner = User::factory()->create();
        $donation = DatabaseNotification::create(['id' => (string) Str::uuid(), 'type' => 'test', 'notifiable_type' => User::class, 'notifiable_id' => $owner->id, 'data' => ['title' => 'Donasi', 'message' => 'Berhasil', 'kind' => 'payment_success'], 'read_at' => null]);
        DatabaseNotification::create(['id' => (string) Str::uuid(), 'type' => 'test', 'notifiable_type' => User::class, 'notifiable_id' => $owner->id, 'data' => ['title' => 'Update', 'message' => 'Dampak', 'kind' => 'impact'], 'read_at' => null]);

        $this->actingAs($owner)->get(route('public.inbox', ['tab' => 'donations']))->assertOk()->assertSee('Berhasil')->assertDontSee('Dampak');
        $this->actingAs($owner)->get(route('public.inbox', ['tab' => 'updates']))->assertOk()->assertSee('Dampak')->assertDontSee('Berhasil');
        $this->actingAs($owner)->get(route('public.inbox.show', $donation->id))->assertOk()->assertSee('Berhasil');
        $this->assertNotNull($donation->fresh()->read_at);
    }

    public function test_notification_detail_is_scoped_to_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = DatabaseNotification::create(['id' => (string) Str::uuid(), 'type' => 'test', 'notifiable_type' => User::class, 'notifiable_id' => $other->id, 'data' => ['title' => 'Private', 'message' => 'Private', 'kind' => 'impact'], 'read_at' => null]);

        $this->actingAs($owner)->get(route('public.inbox.show', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_success_notification_links_to_its_donation_receipt(): void
    {
        $owner = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);
        $notification = DatabaseNotification::create(['id' => (string) Str::uuid(), 'type' => 'test', 'notifiable_type' => User::class, 'notifiable_id' => $owner->id, 'data' => ['title' => 'Pembayaran berhasil', 'message' => 'Berhasil', 'kind' => 'payment_success', 'donation_id' => $donation->id], 'read_at' => null]);

        $this->actingAs($owner)->get(route('public.inbox.show', $notification->id))
            ->assertOk()->assertSee(route('public.receipt', $donation), false)->assertSee('Lihat detail donasi');
    }

    public function test_receipt_is_not_accessible_to_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($other)->get(route('public.receipt', $donation))->assertNotFound();
        $this->actingAs($owner)->get(route('public.receipt', $donation))->assertOk()->assertSee('Receipt Salamaid');
    }

    public function test_owner_can_download_receipt_as_pdf(): void
    {
        $owner = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);
        $response = $this->actingAs($owner)->get(route('public.receipt.download', $donation));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('Content-Disposition');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_another_user_cannot_download_receipt_pdf(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'paid', 'paid_at' => now()]);

        $this->actingAs($other)->get(route('public.receipt.download', $donation))->assertNotFound();
    }

    public function test_owner_can_regenerate_pending_mayar_payment_link(): void
    {
        $owner = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'pending']);
        Payment::create([
            'donation_id' => $donation->id,
            'provider' => 'mayar',
            'order_id' => 'old-payment',
            'channel_code' => 'mayar_checkout',
            'redirect_url' => 'https://example.test/old-payment',
            'status' => 'pending',
            'raw_request_json' => ['name' => 'Public Donor', 'email' => $owner->email, 'mobile' => '+628120000000'],
        ]);
        Http::fake(['*/payments/create' => Http::response(['data' => ['transactionId' => 'new-payment', 'link' => 'https://example.test/new-payment']], 200)]);

        $this->actingAs($owner)->post(route('public.receipt.regenerate-payment', $donation))
            ->assertRedirect(route('public.receipt', $donation))
            ->assertSessionHas('status_message');

        $this->assertDatabaseHas('payments', ['donation_id' => $donation->id, 'order_id' => 'new-payment', 'redirect_url' => 'https://example.test/new-payment']);
    }

    public function test_another_user_cannot_regenerate_payment_link(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'pending']);

        $this->actingAs($other)->post(route('public.receipt.regenerate-payment', $donation))->assertNotFound();
    }

    public function test_owner_can_regenerate_when_initial_payment_record_was_not_created(): void
    {
        $owner = User::factory()->create();
        $donation = Donation::create(['campaign_id' => $this->campaign()->id, 'user_id' => $owner->id, 'gross_amount' => 50000, 'net_amount' => 50000, 'method' => 'mayar', 'status' => 'pending']);
        Http::fake(['*/payments/create' => Http::response(['data' => ['transactionId' => 'recovered-payment', 'link' => 'https://example.test/recovered-payment']], 200)]);

        $this->actingAs($owner)->post(route('public.receipt.regenerate-payment', $donation), ['mobile' => '+628120000000'])
            ->assertRedirect(route('public.receipt', $donation))
            ->assertSessionHas('status_message');

        $this->assertDatabaseHas('payments', ['donation_id' => $donation->id, 'order_id' => 'recovered-payment']);
    }

    public function test_user_can_update_personal_profile_data(): void
    {
        $owner = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.test']);

        $this->actingAs($owner)->put(route('public.account.profile.update'), [
            'name' => 'Updated Name',
            'email' => 'updated@example.test',
            'mobile' => '+628120000000',
        ])->assertRedirect(route('public.account'))->assertSessionHas('status_message');

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'name' => 'Updated Name', 'email' => 'updated@example.test', 'mobile' => '+628120000000']);
    }

    public function test_user_cannot_update_profile_with_duplicate_email(): void
    {
        $owner = User::factory()->create(['email' => 'owner@example.test']);
        User::factory()->create(['email' => 'taken@example.test']);

        $this->actingAs($owner)->put(route('public.account.profile.update'), [
            'name' => $owner->name,
            'email' => 'taken@example.test',
            'mobile' => '+628120000000',
        ])->assertSessionHasErrors('email');
    }

    public function test_authenticated_user_can_open_help_and_privacy_pages(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('public.account.help'))->assertOk()->assertSee('Pusat bantuan');
        $this->actingAs($owner)->get(route('public.account.privacy'))->assertOk()->assertSee('Keamanan dan privasi');
    }
}
