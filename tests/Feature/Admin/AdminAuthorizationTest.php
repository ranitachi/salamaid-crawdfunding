<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Catalog\Entities\Campaign;
use Modules\Core\Entities\Organization;
use Modules\Donation\Entities\Donation;
use Modules\Operations\Entities\Payout;
use Modules\Payment\Entities\Payment;
use Modules\Transparency\Entities\CampaignUpdate;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /** Every policy ability, keyed by the model it applies to. */
    private function policyAbilities(): array
    {
        return [
            [new Campaign, ['viewAny', 'view', 'review', 'publish']],
            [new Organization, ['viewAny', 'view', 'review']],
            [new Donation, ['viewAny', 'view']],
            [new Payment, ['viewAny', 'view', 'manage']],
            [new Payout, ['viewAny', 'view', 'approve', 'reject', 'markPaid']],
            [new CampaignUpdate, ['viewAny', 'view', 'review']],
            [new User, ['updateRole']],
        ];
    }

    /**
     * Probe routes declared the same way real admin area routes must be:
     * auth + admin + permission middleware + policy on the bound model.
     */
    private function registerProbeRoutes(): void
    {
        Route::middleware(['web', 'auth', 'admin', 'admin.permission:payouts.manage', 'can:approve,payout'])
            ->post('/admin/_probe/payouts/{payout}/approve', fn (Payout $payout) => 'approved');

        Route::middleware(['web', 'auth', 'admin', 'can:updateRole,user'])
            ->patch('/admin/_probe/users/{user}/role', fn (User $user) => 'updated');
    }

    private function createPayout(): Payout
    {
        return Payout::create([
            'organization_id' => (string) Str::uuid(),
            'amount' => 1_000_000,
            'status' => 'requested',
            'requested_by' => (string) Str::uuid(),
        ]);
    }

    public function test_super_admin_can_access_every_area_and_action(): void
    {
        $superAdmin = User::factory()->role('super_admin')->create();

        $permissions = collect(config('admin.permissions'))->flatten()
            ->reject(fn ($p) => $p === '*')
            ->push('users.roles.manage')
            ->unique();

        foreach ($permissions as $permission) {
            $this->assertTrue($superAdmin->hasAdminPermission($permission), $permission);
        }

        foreach ($this->policyAbilities() as [$model, $abilities]) {
            foreach ($abilities as $ability) {
                $this->assertTrue(
                    Gate::forUser($superAdmin)->allows($ability, $model),
                    class_basename($model).'::'.$ability,
                );
            }
        }
    }

    public function test_reviewer_cannot_approve_payout(): void
    {
        $this->registerProbeRoutes();
        $reviewer = User::factory()->role('reviewer')->create();
        $payout = $this->createPayout();

        $this->assertTrue(Gate::forUser($reviewer)->denies('approve', $payout));
        $this->assertTrue(Gate::forUser($reviewer)->denies('viewAny', Payout::class));

        $this->actingAs($reviewer)
            ->post("/admin/_probe/payouts/{$payout->id}/approve")
            ->assertForbidden();
    }

    public function test_finance_can_approve_payout(): void
    {
        $this->registerProbeRoutes();
        $payout = $this->createPayout();

        $this->actingAs(User::factory()->role('finance')->create())
            ->post("/admin/_probe/payouts/{$payout->id}/approve")
            ->assertOk()
            ->assertSee('approved');
    }

    public function test_finance_cannot_change_user_role(): void
    {
        $this->registerProbeRoutes();
        $finance = User::factory()->role('finance')->create();
        $target = User::factory()->create();

        $this->assertTrue(Gate::forUser($finance)->denies('updateRole', $target));

        $this->actingAs($finance)
            ->patch("/admin/_probe/users/{$target->id}/role", ['role' => 'super_admin'])
            ->assertForbidden();
    }

    public function test_super_admin_can_change_user_role_route(): void
    {
        $this->registerProbeRoutes();
        $target = User::factory()->create();

        $this->actingAs(User::factory()->role('super_admin')->create())
            ->patch("/admin/_probe/users/{$target->id}/role", ['role' => 'support'])
            ->assertOk();
    }

    public function test_non_admin_and_inactive_users_have_no_permissions(): void
    {
        $users = [
            User::factory()->create(),
            User::factory()->role('super_admin')->inactive()->create(),
        ];

        foreach ($users as $user) {
            $this->assertFalse($user->hasAdminPermission('dashboard.view'));

            foreach ($this->policyAbilities() as [$model, $abilities]) {
                foreach ($abilities as $ability) {
                    $this->assertTrue(Gate::forUser($user)->denies($ability, $model));
                }
            }
        }
    }

    public function test_role_matrix_follows_config(): void
    {
        $support = User::factory()->role('support')->create();
        $this->assertTrue(Gate::forUser($support)->allows('viewAny', Payment::class));
        $this->assertTrue(Gate::forUser($support)->denies('manage', new Payment));
        $this->assertTrue(Gate::forUser($support)->denies('review', new Campaign));

        $reviewer = User::factory()->role('reviewer')->create();
        $this->assertTrue(Gate::forUser($reviewer)->allows('review', new Campaign));
        $this->assertTrue(Gate::forUser($reviewer)->denies('publish', new Campaign));
        $this->assertTrue(Gate::forUser($reviewer)->denies('viewAny', Payment::class));

        $admin = User::factory()->role('admin')->create();
        $this->assertTrue(Gate::forUser($admin)->allows('publish', new Campaign));
        $this->assertTrue(Gate::forUser($admin)->denies('approve', new Payout));
        $this->assertTrue(Gate::forUser($admin)->denies('updateRole', new User));
    }
}
