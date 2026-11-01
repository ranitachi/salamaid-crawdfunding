<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_audit_is_persisted_and_sensitive_values_are_redacted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $subject = User::factory()->create(['role' => 'support']);

        $this->actingAs($admin);
        app(AdminAuditLogger::class)->log('user.test', $subject, ['password' => 'secret', 'name' => 'Before'], ['token' => 'secret', 'name' => 'After'], null, $admin, ['signature' => 'secret']);

        $log = AdminAuditLog::query()->latest('id')->firstOrFail();
        $this->assertSame('[REDACTED]', $log->before['password']);
        $this->assertSame('[REDACTED]', $log->after['token']);
        $this->assertSame('[REDACTED]', $log->context['signature']);
        $this->assertSame($admin->id, $log->actor_id);
    }

    public function test_admin_can_filter_audit_logs_and_non_admin_cannot_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        AdminAuditLog::create(['action' => 'campaign.publish', 'subject_type' => User::class, 'subject_id' => '1', 'actor_id' => $admin->id]);

        $this->actingAs($admin)->get('/admin/audit-logs?action=campaign.publish')->assertOk()->assertSee('campaign.publish');
        $this->actingAs($user)->get('/admin/audit-logs')->assertForbidden();
    }
}
