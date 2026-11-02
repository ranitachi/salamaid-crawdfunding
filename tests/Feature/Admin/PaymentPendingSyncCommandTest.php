<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Payment\App\Jobs\SyncPaymentStatusJob;
use Modules\Payment\Entities\Payment;
use Tests\TestCase;

class PaymentPendingSyncCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_command_dispatches_only_stale_pending_mayar_payments(): void
    {
        $stale = $this->payment('pending', 'mayar', now()->subMinutes(20));
        $this->payment('pending', 'mayar', now()->subMinutes(5));
        $this->payment('paid', 'mayar', now()->subMinutes(20));
        $this->payment('pending', 'demo', now()->subMinutes(20));

        $exitCode = Artisan::call('payment:sync-pending');

        $this->assertSame(0, $exitCode);
        Queue::assertPushed(SyncPaymentStatusJob::class, 1);
        Queue::assertPushed(SyncPaymentStatusJob::class, fn (SyncPaymentStatusJob $job): bool =>
            $job->paymentId === $stale->id && $job->trigger === 'scheduled'
        );
    }

    public function test_command_honours_limit_and_minutes_options(): void
    {
        $first = $this->payment('pending', 'mayar', now()->subMinutes(30));
        $this->payment('pending', 'mayar', now()->subMinutes(20));

        Artisan::call('payment:sync-pending', ['--minutes' => 10, '--limit' => 1]);

        Queue::assertPushed(SyncPaymentStatusJob::class, 1);
        Queue::assertPushed(SyncPaymentStatusJob::class, fn (SyncPaymentStatusJob $job): bool => $job->paymentId === $first->id);
    }

    private function payment(string $status, string $provider, \DateTimeInterface $createdAt): Payment
    {
        $organization = Organization::create(['slug' => fake()->unique()->slug(), 'name' => 'Org']);
        $campaign = Campaign::create([
            'organization_id' => $organization->id,
            'title' => 'Campaign Sync',
            'description' => 'Deskripsi',
            'target_amount' => 1_000_000,
            'status' => 'published',
        ]);
        $donation = Donation::create([
            'campaign_id' => $campaign->id,
            'gross_amount' => 100_000,
            'net_amount' => 100_000,
            'method' => 'mayar',
            'status' => $status,
        ]);

        $payment = Payment::create([
            'donation_id' => $donation->id,
            'provider' => $provider,
            'order_id' => 'tx-'.fake()->unique()->uuid(),
            'channel_code' => 'mayar_checkout',
            'status' => $status,
        ]);
        $payment->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

        return $payment;
    }
}
