<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Payment\App\Exceptions\MayarRequestException;
use Modules\Payment\App\Jobs\SyncPaymentStatusJob;
use Modules\Payment\App\Services\ApplyPaymentStatusAction;
use Modules\Payment\App\Services\MayarStatusMapper;
use Modules\Payment\App\Services\SyncPaymentStatusAction;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\PaymentSyncLog;
use Modules\Payment\Entities\Webhook;
use Tests\TestCase;

class MayarPaymentSyncTest extends TestCase
{
    use RefreshDatabase;

    private const API_KEY = 'sk-sandbox-SECRET-never-log';
    private const BASE = 'https://api.mayar.io/hl/v2';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Config::set('payment.mayar.api_key', self::API_KEY);
        Config::set('payment.mayar.base_url', self::BASE);
        Config::set('payment.mayar.webhook_token', 'hook-token');
        Config::set('payment.webhooks.providers.mayar.token', 'hook-token');
    }

    private function payment(string $status = 'pending', string $provider = 'mayar', int $amount = 100_000): Payment
    {
        $organization = Organization::create(['slug' => 'org-'.fake()->unique()->slug(), 'name' => 'Org']);
        $campaign = Campaign::create([
            'organization_id' => $organization->id, 'title' => 'Campaign Sync', 'description' => 'Deskripsi',
            'target_amount' => 1_000_000, 'status' => 'published',
        ]);
        $donation = Donation::create([
            'campaign_id' => $campaign->id, 'gross_amount' => $amount, 'net_amount' => $amount,
            'method' => 'mayar', 'status' => $status,
        ]);

        return Payment::create([
            'donation_id' => $donation->id, 'provider' => $provider,
            'order_id' => 'tx-'.fake()->unique()->uuid(), 'channel_code' => 'mayar_checkout', 'status' => $status,
        ]);
    }

    private function fakeInquiry(Payment $payment, string $status, ?int $amount = 100_000, ?string $id = null): void
    {
        Http::fake([self::BASE.'/transactions/*' => Http::response([
            'statusCode' => 200, 'messages' => 'success',
            'data' => ['id' => $id ?? $payment->order_id, 'status' => $status, 'amount' => $amount, 'paymentMethod' => 'QRIS',
                'customer' => ['email' => 'donor@example.test']],
        ])]);
    }

    private function sync(Payment $payment, ?User $actor = null): PaymentSyncLog
    {
        return app(SyncPaymentStatusAction::class)->execute($payment->load('donation'), 'manual', $actor);
    }

    private function admin(string $role): User
    {
        return User::factory()->role($role)->create();
    }

    // ---- Inquiry ---------------------------------------------------------

    public function test_successful_inquiry_marks_payment_and_donation_paid(): void
    {
        $payment = $this->payment();
        $this->fakeInquiry($payment, 'paid');

        $log = $this->sync($payment);

        $this->assertSame('updated', $log->outcome);
        $this->assertSame(['pending', 'paid', 'paid'], [$log->status_before, $log->status_after, $log->provider_status]);
        $this->assertArrayNotHasKey('customer', $log->response_summary, 'Customer PII is not stored.');
        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);
        $this->assertDatabaseHas('donations', ['id' => $payment->donation_id, 'status' => 'paid']);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === self::BASE.'/transactions/'.$payment->order_id
            && $request->hasHeader('Authorization', 'Bearer '.self::API_KEY));
    }

    public function test_failed_inquiry_is_logged_reduced_and_changes_nothing(): void
    {
        Log::spy();
        $payment = $this->payment();
        Http::fake([self::BASE.'/transactions/*' => Http::response(['statusCode' => 503, 'messages' => 'down'], 503)]);

        try {
            $this->sync($payment);
            $this->fail('Expected MayarRequestException');
        } catch (MayarRequestException $exception) {
            $this->assertTrue($exception->retryable);
            $this->assertSame(503, $exception->httpStatus);
            $this->assertStringNotContainsString(self::API_KEY, $exception->getMessage());
        }

        $this->assertSame('pending', $payment->fresh()->status);
        $log = PaymentSyncLog::sole();
        $this->assertSame(['failed', 503], [$log->outcome, $log->http_status]);

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context) {
            return $message === 'payment.sync.failed'
                && ! str_contains(json_encode($context), self::API_KEY);
        })->once();
    }

    public function test_inquiry_error_classification(): void
    {
        $payment = $this->payment();

        // One stub per case: later Http::fake() calls do not replace an earlier matching stub.
        $responses = [
            fn () => Http::response(['statusCode' => 404, 'messages' => 'Not found'], 404),
            fn () => Http::response([], 429, ['Retry-After' => '42']),
            fn () => throw new ConnectionException('cURL error 28: timed out with Bearer '.self::API_KEY),
        ];
        Http::fake(function () use (&$responses) {
            return array_shift($responses)();
        });

        $this->assertProviderError($payment, retryable: false, status: 404);

        $error = $this->assertProviderError($payment, retryable: true, status: 429);
        $this->assertSame(42, $error->retryAfter);

        $error = $this->assertProviderError($payment, retryable: true, status: null);
        $this->assertStringNotContainsString(self::API_KEY, $error->getMessage());

        Config::set('payment.mayar.api_key', null);
        $this->assertProviderError($payment, retryable: false, status: null);

        $this->assertSame('pending', $payment->fresh()->status);
    }

    private function assertProviderError(Payment $payment, bool $retryable, ?int $status): MayarRequestException
    {
        try {
            $this->sync($payment);
        } catch (MayarRequestException $exception) {
            $this->assertSame($retryable, $exception->retryable);
            $this->assertSame($status, $exception->httpStatus);

            return $exception;
        }

        $this->fail('Expected MayarRequestException');
    }

    // ---- Mapping ---------------------------------------------------------

    public function test_status_mapping_is_explicit(): void
    {
        $this->assertSame('paid', MayarStatusMapper::toInternal('paid'));
        $this->assertSame('paid', MayarStatusMapper::toInternal(' PAID '));
        $this->assertSame('pending', MayarStatusMapper::toInternal('unpaid'));
        $this->assertSame('pending', MayarStatusMapper::toInternal('created'));
        $this->assertSame('expired', MayarStatusMapper::toInternal('expired'));
        $this->assertNull(MayarStatusMapper::toInternal('SUCCESS'), 'Webhook delivery status is not a payment status.');
        $this->assertNull(MayarStatusMapper::toInternal('refunded'));
        $this->assertNull(MayarStatusMapper::toInternal(null));
    }

    public function test_unknown_or_inconsistent_provider_data_never_changes_status(): void
    {
        $unknown = $this->payment();
        $this->fakeInquiry($unknown, 'refunded');
        $this->assertSame('unmapped', $this->sync($unknown)->outcome);

        $wrongId = $this->payment();
        $this->fakeInquiry($wrongId, 'paid', id: 'tx-someone-else');
        $this->assertSame('mismatch', $this->sync($wrongId)->outcome);

        $underpaid = $this->payment();
        $this->fakeInquiry($underpaid, 'paid', amount: 1_000);
        $this->assertSame('mismatch', $this->sync($underpaid)->outcome);

        foreach ([$unknown, $wrongId, $underpaid] as $payment) {
            $this->assertSame('pending', $payment->fresh()->status);
            $this->assertSame('pending', $payment->donation->fresh()->status);
        }
    }

    public function test_status_transitions_are_monotonic(): void
    {
        $apply = app(ApplyPaymentStatusAction::class);

        $paid = $this->payment('paid');
        $this->assertFalse($apply->execute($paid, 'expired')['changed']);
        $this->assertFalse($apply->execute($paid, 'pending')['changed']);
        $this->assertSame('paid', $paid->fresh()->status);

        $expired = $this->payment('expired');
        $this->assertFalse($apply->execute($expired, 'failed')['changed']);
        $this->assertTrue($apply->execute($expired, 'paid')['changed'], 'Late payment confirmed by provider is accepted.');

        $pending = $this->payment();
        $this->fakeInquiry($pending, 'unpaid');
        $this->assertSame('unchanged', $this->sync($pending)->outcome);
        $this->assertSame('pending', $pending->fresh()->status);
    }

    // ---- Queue / retry ---------------------------------------------------

    public function test_sync_job_retries_transient_failure_with_backoff_then_succeeds(): void
    {
        Config::set('queue.default', 'database');
        $payment = $this->payment();
        Http::fake([self::BASE.'/transactions/*' => Http::sequence()
            ->push(['statusCode' => 502, 'messages' => 'bad gateway'], 502)
            ->push(['statusCode' => 200, 'data' => ['id' => $payment->order_id, 'status' => 'paid', 'amount' => 100_000]])]);

        $job = new SyncPaymentStatusJob($payment->id);
        $this->assertSame(5, $job->tries);
        $this->assertSame([30, 120, 600, 1800], $job->backoff());

        SyncPaymentStatusJob::dispatch($payment->id, 'manual');
        Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame(1, DB::table('jobs')->count(), 'Released back to the queue for a retry.');
        $this->assertGreaterThan(now()->getTimestamp() + 20, DB::table('jobs')->value('available_at'), 'Backoff delay applied.');

        $this->travel(31)->seconds();
        Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(0, DB::table('failed_jobs')->count());
        $this->assertSame(
            [['manual', 1, 'failed'], ['retry', 2, 'updated']],
            PaymentSyncLog::orderBy('attempt')->get()->map(fn ($l) => [$l->trigger, $l->attempt, $l->outcome])->all(),
        );
    }

    public function test_sync_job_fails_immediately_on_non_retryable_error(): void
    {
        Config::set('queue.default', 'database');
        $payment = $this->payment();
        Http::fake([self::BASE.'/transactions/*' => Http::response(['statusCode' => 401, 'messages' => 'Unauthorized'], 401)]);

        SyncPaymentStatusJob::dispatch($payment->id);
        Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

        $this->assertSame(0, DB::table('jobs')->count());
        $this->assertSame(1, DB::table('failed_jobs')->count());
        $this->assertStringNotContainsString(self::API_KEY, (string) DB::table('failed_jobs')->value('exception'));
        $this->assertSame('pending', $payment->fresh()->status);
    }

    // ---- Webhooks --------------------------------------------------------

    public function test_mayar_webhook_is_confirmed_by_inquiry_and_duplicates_are_processed_once(): void
    {
        $payment = $this->payment();
        $this->fakeInquiry($payment, 'paid');
        $body = ['event' => 'payment.received', 'data' => ['transactionId' => $payment->order_id, 'status' => 'SUCCESS']];

        $this->postJson('/api/v1/payment/webhooks/mayar?token=hook-token', $body)->assertOk();
        $this->postJson('/api/v1/payment/webhooks/mayar?token=hook-token', $body)->assertOk();

        $this->assertSame(1, Webhook::count());
        $this->assertNotNull(Webhook::sole()->processed_at);
        Http::assertSentCount(1);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(1, PaymentSyncLog::where('trigger', 'webhook')->count());
    }

    public function test_mayar_reminder_webhook_with_success_delivery_status_does_not_mark_paid(): void
    {
        $payment = $this->payment();
        $this->fakeInquiry($payment, 'unpaid');

        $this->postJson('/api/v1/payment/webhooks/mayar?token=hook-token', [
            'event' => 'payment.reminder',
            'data' => ['id' => $payment->order_id, 'transactionId' => $payment->order_id, 'status' => 'SUCCESS', 'transactionStatus' => 'created'],
        ])->assertOk();

        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertSame('pending', $payment->donation->fresh()->status);
    }

    public function test_failed_webhook_processing_is_recorded_and_can_be_retried_by_finance(): void
    {
        $payment = $this->payment();
        Http::fake([self::BASE.'/transactions/*' => Http::sequence()
            ->push(['statusCode' => 500], 500)
            ->push(['statusCode' => 200, 'data' => ['id' => $payment->order_id, 'status' => 'paid', 'amount' => 100_000]])]);

        $this->postJson('/api/v1/payment/webhooks/mayar?token=hook-token', [
            'event' => 'payment.received', 'data' => ['transactionId' => $payment->order_id, 'status' => 'SUCCESS'],
        ])->assertStatus(503)->assertJson(['received' => true, 'processed' => false]);

        $webhook = Webhook::sole();
        $this->assertNull($webhook->processed_at);
        $this->assertSame(1, $webhook->attempts);
        $this->assertStringContainsString('server error 500', $webhook->last_error);

        $this->actingAs($this->admin('finance'))
            ->post(route('admin.payments.webhooks.retry', $webhook))
            ->assertRedirect(route('admin.payments.show', $payment))
            ->assertSessionHas('success');

        $this->assertNotNull($webhook->fresh()->processed_at);
        $this->assertSame('paid', $payment->fresh()->status);
    }

    public function test_signed_provider_webhook_cannot_downgrade_paid_payment(): void
    {
        Config::set('payment.webhooks.providers.test', [
            'algorithm' => 'sha256', 'secret' => 'test-secret', 'signature_header' => 'X-Test-Signature',
            'order_id_field' => 'order_id', 'event_field' => 'event',
        ]);
        $payment = $this->payment('pending', 'test');

        foreach ([['payment.paid', 'paid'], ['payment.failed', 'failed']] as [$event, $status]) {
            $body = ['order_id' => $payment->order_id, 'event' => $event, 'status' => $status];
            $this->withHeaders(['X-Test-Signature' => hash_hmac('sha256', json_encode($body), 'test-secret')])
                ->postJson('/api/v1/payment/webhooks/test', $body)->assertOk();
        }

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame(2, Webhook::whereNotNull('processed_at')->count());
    }

    // ---- Admin authorization ---------------------------------------------

    public function test_only_payments_manage_can_trigger_sync_and_retry(): void
    {
        $payment = $this->payment();
        $this->fakeInquiry($payment, 'paid');
        $webhook = Webhook::create(['provider' => 'mayar', 'event' => 'payment.received', 'order_id' => $payment->order_id, 'payload' => []]);

        $this->post(route('admin.payments.sync', $payment))->assertRedirect(route('admin.login'));

        foreach (['admin', 'support', 'reviewer', 'user'] as $role) {
            $user = $this->admin($role);
            $this->actingAs($user)->post(route('admin.payments.sync', $payment))->assertForbidden();
            $this->actingAs($user)->post(route('admin.payments.webhooks.retry', $webhook))->assertForbidden();
        }

        Http::assertNothingSent();
        $this->assertSame('pending', $payment->fresh()->status);
        $this->assertNull($webhook->fresh()->processed_at);
    }

    public function test_finance_sync_uses_provider_status_and_ignores_requested_status(): void
    {
        $finance = $this->admin('finance');
        $payment = $this->payment();
        $this->fakeInquiry($payment, 'expired');

        $this->actingAs($finance)
            ->post(route('admin.payments.sync', $payment), ['status' => 'paid'])
            ->assertRedirect(route('admin.payments.show', $payment))
            ->assertSessionHas('success');

        $this->assertSame('expired', $payment->fresh()->status);
        $log = PaymentSyncLog::sole();
        $this->assertSame([$finance->id, 'manual', 'updated'], [(int) $log->actor_id, $log->trigger, $log->outcome]);

        // Sync results are visible to read-only payment roles; the action button is not.
        foreach (['support', 'admin'] as $role) {
            $this->actingAs($this->admin($role))->get(route('admin.payments.show', $payment))
                ->assertOk()->assertSee('Riwayat sinkronisasi provider')->assertSee('expired')
                ->assertDontSee(route('admin.payments.sync', $payment));
        }

        $this->actingAs($finance)->get(route('admin.payments.show', $payment))->assertSee(route('admin.payments.sync', $payment));
    }
}
