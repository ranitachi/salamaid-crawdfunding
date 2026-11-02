<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_requires_reports_permission(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get('/admin/reports')->assertForbidden();
    }

    public function test_admin_can_filter_and_export_aggregate_report(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        DB::table('campaigns')->insert([
            'id' => 'campaign-report-1', 'title' => 'Report campaign', 'slug' => 'report-campaign',
            'organization_id' => 'organization-report-1', 'category_id' => null,
            'description' => 'Campaign for report test', 'status' => 'published', 'target_amount' => 100000,
            'collected_amount' => 10000, 'deadline_at' => now()->addDay(), 'location_label' => null,
            'cover_url' => null, 'is_featured' => false, 'meta_json' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('donations')->insert([
            'id' => 'donation-report-1', 'campaign_id' => 'campaign-report-1', 'user_id' => null,
            'gross_amount' => 100000, 'fee_platform' => 1000, 'fee_channel' => 500, 'net_amount' => 98500,
            'method' => 'qris', 'anonymous' => true, 'message' => null, 'ref_code' => null,
            'status' => 'paid', 'paid_at' => now(), 'expired_at' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('payments')->insert([
            'id' => 'payment-report-1', 'donation_id' => 'donation-report-1', 'provider' => 'mayar',
            'order_id' => 'REPORT-1', 'channel_code' => 'QRIS', 'status' => 'paid', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/reports?date_from='.now()->toDateString().'&date_to='.now()->toDateString());
        $response->assertOk()->assertSee('Campaign dibuat', false)->assertSee('published', false)->assertSee('1.500', false);

        $export = $this->actingAs($admin)->get('/admin/reports/export?date_from='.now()->toDateString());
        $export->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertHeader('content-disposition');

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => 'report.export',
            'actor_id' => $admin->id,
        ]);

        $audit = AdminAuditLog::query()->where('action', 'report.export')->latest('id')->firstOrFail();
        $this->assertSame(now()->toDateString(), $audit->context['filters']['date_from']);
        $this->assertSame(14, $audit->after['metric_rows']);
    }
}
