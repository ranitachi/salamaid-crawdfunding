<?php

namespace Tests\Feature\Admin;

use App\Contracts\AdminAuditLogger;
use App\Events\Admin\UserRoleChanged;
use App\Events\Admin\UserStatusChanged;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Modules\Core\Entities\OrganizationMember;

class UserManagementTest extends AdminReviewTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_super_admin_can_list_and_filter_users(): void
    {
        $actor = $this->user('super_admin');
        $support = $this->user('support');
        $reviewer = $this->user('reviewer');
        $inactive = $this->user('support', false);

        $this->actingAs($actor)
            ->get(route('admin.users.index', ['role' => 'support']))
            ->assertOk()
            ->assertSee($support->email)
            ->assertSee($inactive->email)
            ->assertDontSee($reviewer->email)
            ->assertViewHas('users', fn ($paginator) => $paginator->total() === 2);

        $this->actingAs($actor)
            ->get(route('admin.users.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee($inactive->email)
            ->assertDontSee($support->email);

        $this->actingAs($actor)
            ->get(route('admin.users.index', ['search' => $reviewer->email]))
            ->assertOk()
            ->assertSee($reviewer->email)
            ->assertDontSee($support->email);
    }

    public function test_admin_without_user_permission_is_denied(): void
    {
        $admin = $this->user('admin');
        $target = $this->user('support');

        $this->actingAs($admin)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.users.show', $target))->assertForbidden();
        $this->actingAs($admin)
            ->patch(route('admin.users.role.update', $target), ['role' => 'reviewer'])
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('admin.users.deactivate', $target))
            ->assertForbidden();
        $this->actingAs($admin)
            ->post(route('admin.users.password-reset', $target))
            ->assertForbidden();

        $this->assertSame('support', $target->fresh()->role);
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_super_admin_can_change_role_with_audit_and_event(): void
    {
        $actor = $this->user('super_admin');
        $target = $this->user('support');

        Event::fake([UserRoleChanged::class]);

        $logger = Mockery::mock(AdminAuditLogger::class);
        $logger->shouldReceive('log')
            ->once()
            ->withArgs(function (
                string $action,
                $subject,
                array $before,
                array $after,
                ?string $reason,
                $loggedActor,
                array $context,
            ) use ($target, $actor) {
                return $action === 'user.role_changed'
                    && $subject->getKey() === $target->getKey()
                    && $before['role'] === 'support'
                    && $after['role'] === 'reviewer'
                    && $loggedActor?->getKey() === $actor->getKey()
                    && $context['entity'] === 'user';
            });

        $this->app->instance(AdminAuditLogger::class, $logger);

        $this->actingAs($actor)
            ->patch(route('admin.users.role.update', $target), ['role' => 'reviewer'])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $this->assertSame('reviewer', $target->fresh()->role);

        Event::assertDispatched(
            UserRoleChanged::class,
            fn (UserRoleChanged $event) => $event->user->getKey() === $target->getKey()
                && $event->from === 'support'
                && $event->to === 'reviewer',
        );
    }

    public function test_invalid_role_is_rejected(): void
    {
        $actor = $this->user('super_admin');
        $target = $this->user('support');

        $this->actingAs($actor)
            ->patch(route('admin.users.role.update', $target), ['role' => 'hacker'])
            ->assertSessionHasErrors('role');

        $this->assertSame('support', $target->fresh()->role);
    }

    public function test_super_admin_cannot_change_own_role_or_deactivate_self(): void
    {
        $actor = $this->user('super_admin');

        $this->actingAs($actor)
            ->patch(route('admin.users.role.update', $actor), ['role' => 'reviewer'])
            ->assertRedirect(route('admin.users.show', $actor))
            ->assertSessionHas('error');

        $this->actingAs($actor)
            ->post(route('admin.users.deactivate', $actor))
            ->assertRedirect(route('admin.users.show', $actor))
            ->assertSessionHas('error');

        $actor->refresh();

        $this->assertSame('super_admin', $actor->role);
        $this->assertTrue($actor->is_active);
    }

    public function test_super_admin_can_activate_and_deactivate_user_with_events(): void
    {
        $actor = $this->user('super_admin');
        $target = $this->user('support');

        Event::fake([UserStatusChanged::class]);

        $this->actingAs($actor)
            ->post(route('admin.users.deactivate', $target))
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $this->assertFalse($target->fresh()->is_active);

        $this->actingAs($actor)
            ->post(route('admin.users.activate', $target))
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success');

        $this->assertTrue($target->fresh()->is_active);

        Event::assertDispatched(
            UserStatusChanged::class,
            fn (UserStatusChanged $event) => $event->user->getKey() === $target->getKey() && $event->active === false,
        );
        Event::assertDispatched(
            UserStatusChanged::class,
            fn (UserStatusChanged $event) => $event->user->getKey() === $target->getKey() && $event->active === true,
        );
    }

    public function test_deactivation_is_audited(): void
    {
        $actor = $this->user('super_admin');
        $target = $this->user('support');

        $logger = Mockery::mock(AdminAuditLogger::class);
        $logger->shouldReceive('log')
            ->once()
            ->withArgs(fn (
                string $action,
                $subject,
                array $before,
                array $after,
                ?string $reason,
                $loggedActor,
                array $context,
            ) => $action === 'user.deactivated'
                && $subject->getKey() === $target->getKey()
                && $before['is_active'] === true
                && $after['is_active'] === false
                && $loggedActor?->getKey() === $actor->getKey());

        $this->app->instance(AdminAuditLogger::class, $logger);

        $this->actingAs($actor)
            ->post(route('admin.users.deactivate', $target))
            ->assertSessionHas('success');
    }

    public function test_password_reset_sends_link_without_changing_or_exposing_password(): void
    {
        Notification::fake();

        $actor = $this->user('super_admin');
        $target = $this->user('support');
        $originalHash = $target->password;

        $response = $this->actingAs($actor)
            ->post(route('admin.users.password-reset', $target));

        $response->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHas('success')
            ->assertDontSee('password');

        Notification::assertSentTo($target, ResetPassword::class);

        $this->assertSame($originalHash, $target->fresh()->password);
        $this->assertTrue(Hash::check('password', $target->fresh()->password));
    }

    public function test_role_index_shows_usage_and_blocks_deleting_roles_in_use(): void
    {
        $actor = $this->user('super_admin');
        $this->user('support');

        $this->actingAs($actor)
            ->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('support')
            ->assertSee('Dipakai');

        $this->actingAs($actor)
            ->delete(route('admin.roles.destroy', 'support'))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'masih dipakai'));

        $this->actingAs($actor)
            ->delete(route('admin.roles.destroy', 'finance'))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'config/admin.php'));

        $this->assertArrayHasKey('support', config('admin.roles'));
        $this->assertArrayHasKey('finance', config('admin.roles'));
    }

    public function test_user_detail_shows_organization_memberships(): void
    {
        $actor = $this->user('super_admin');
        $target = $this->user('support');
        $organization = $this->organization('verified');

        OrganizationMember::create([
            'organization_id' => $organization->id,
            'user_id' => $target->id,
            'role' => 'admin',
        ]);

        $this->actingAs($actor)
            ->get(route('admin.users.show', $target))
            ->assertOk()
            ->assertSee($organization->name)
            ->assertSee('admin');
    }
}
