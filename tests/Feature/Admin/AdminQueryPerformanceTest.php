<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Donation\Entities\Donation;
use Modules\Operations\Entities\Payout;
use Modules\Payment\Entities\Payment;
use Modules\Payment\Entities\Webhook;

/**
 * A12: admin list pages must not issue queries per row (N+1). The query count
 * for a page with 2 rows must equal the count with 8 rows.
 */
class AdminQueryPerformanceTest extends AdminReviewTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function seedRows(int $count): void
    {
        $finance = $this->user('finance');

        for ($i = 0; $i < $count; $i++) {
            $organization = $this->organization('verified');
            $campaign = $this->campaign($organization, 'published', ['category_id' => $this->category('Kat '.Str::random(5))->id]);
            $this->media($campaign);
            $donation = Donation::create([
                'campaign_id' => $campaign->id, 'user_id' => $this->user('user')->id, 'gross_amount' => 10_000,
                'net_amount' => 10_000, 'method' => 'qris', 'status' => 'paid', 'paid_at' => now(),
            ]);
            $payment = Payment::create([
                'donation_id' => $donation->id, 'provider' => 'mayar', 'order_id' => 'tx-'.Str::uuid(),
                'channel_code' => 'mayar_checkout', 'status' => 'paid',
            ]);
            Webhook::create(['provider' => 'mayar', 'event' => 'payment.received', 'order_id' => $payment->order_id, 'payload' => []]);
            Payout::create([
                'organization_id' => $organization->id, 'amount' => 1_000, 'status' => 'requested', 'requested_by' => $finance->id,
            ]);
        }
    }

    private function queriesFor(string $uri): int
    {
        $superAdmin = $this->user('super_admin');
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($superAdmin)->get($uri)->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public function test_admin_list_pages_have_constant_query_count(): void
    {
        $pages = [
            '/admin/organizations', '/admin/campaigns', '/admin/donations', '/admin/payments',
            '/admin/webhooks', '/admin/payouts', '/admin/users', '/admin/audit-logs', '/admin/reports', '/admin',
        ];

        $this->seedRows(2);
        $small = array_map(fn ($uri) => $this->queriesFor($uri), $pages);

        $this->seedRows(6);
        $large = array_map(fn ($uri) => $this->queriesFor($uri), $pages);

        foreach ($pages as $i => $uri) {
            $this->assertSame($small[$i], $large[$i], "{$uri}: {$small[$i]} queries with 2 rows vs {$large[$i]} with 8 rows (N+1).");
            $this->assertLessThan(40, $large[$i], "{$uri} issues too many queries.");
        }
    }

    public function test_list_pages_are_paginated(): void
    {
        $this->seedRows(1);
        $superAdmin = $this->user('super_admin');

        foreach (['/admin/donations?page=999', '/admin/payments?page=999', '/admin/payouts?page=999', '/admin/campaigns?page=999'] as $uri) {
            $this->actingAs($superAdmin)->get($uri)->assertOk();
        }

        $this->actingAs($superAdmin)->get('/admin/campaigns?per_page=100000')->assertSessionHasErrors('per_page');
    }
}
