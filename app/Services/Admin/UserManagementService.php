<?php

namespace App\Services\Admin;

use App\Contracts\AdminAuditLogger;
use App\Events\Admin\UserPasswordResetLinkSent;
use App\Events\Admin\UserRoleChanged;
use App\Events\Admin\UserStatusChanged;
use App\Exceptions\AdminActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class UserManagementService
{
    public function __construct(private AdminAuditLogger $audit)
    {
    }

    public function updateRole(User $target, string $role, User $actor): User
    {
        $this->assertKnownRole($role);

        if ($target->role === $role) {
            return $target;
        }

        if ($target->is($actor)) {
            throw AdminActionException::requirement('Tidak dapat mengubah role akun sendiri.');
        }

        return DB::transaction(function () use ($target, $role, $actor) {
            /** @var User $locked */
            $locked = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->role === 'super_admin' && $locked->is_active && $role !== 'super_admin') {
                $this->assertAnotherActiveSuperAdminExists($locked);
            }

            $before = $locked->only(['role', 'is_active']);
            $from = (string) $locked->role;

            $locked->forceFill(['role' => $role])->save();

            $after = $locked->only(['role', 'is_active']);

            $this->audit->log(
                'user.role_changed',
                $locked,
                $before,
                $after,
                null,
                $actor,
                ['module' => 'Core', 'entity' => 'user'],
            );

            event(new UserRoleChanged($locked, $from, $role, $actor));

            return $locked->refresh();
        });
    }

    public function activate(User $target, User $actor): User
    {
        if ($target->is_active) {
            return $target;
        }

        return $this->setActive($target, true, $actor, 'user.activated');
    }

    public function deactivate(User $target, User $actor): User
    {
        if ($target->is($actor)) {
            throw AdminActionException::requirement('Tidak dapat menonaktifkan akun sendiri.');
        }

        if (! $target->is_active) {
            return $target;
        }

        return $this->setActive($target, false, $actor, 'user.deactivated');
    }

    public function sendPasswordResetLink(User $target, User $actor): string
    {
        $status = Password::sendResetLink(['email' => $target->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw AdminActionException::requirement(
                'Tautan reset password tidak dapat dikirim. Periksa konfigurasi email atau coba lagi nanti.',
            );
        }

        $this->audit->log(
            'user.password_reset_link_sent',
            $target,
            [],
            [],
            null,
            $actor,
            ['module' => 'Core', 'entity' => 'user'],
        );

        event(new UserPasswordResetLinkSent($target, $actor));

        return $status;
    }

    private function setActive(User $target, bool $active, User $actor, string $action): User
    {
        return DB::transaction(function () use ($target, $active, $actor, $action) {
            /** @var User $locked */
            $locked = User::query()->whereKey($target->getKey())->lockForUpdate()->firstOrFail();

            if (! $active && $locked->role === 'super_admin' && $locked->is_active) {
                $this->assertAnotherActiveSuperAdminExists($locked);
            }

            $before = $locked->only(['role', 'is_active']);

            $locked->forceFill(['is_active' => $active])->save();

            $after = $locked->only(['role', 'is_active']);

            $this->audit->log(
                $action,
                $locked,
                $before,
                $after,
                null,
                $actor,
                ['module' => 'Core', 'entity' => 'user'],
            );

            event(new UserStatusChanged($locked, $active, $actor));

            return $locked->refresh();
        });
    }

    private function assertKnownRole(string $role): void
    {
        if (! array_key_exists($role, config('admin.roles', []))) {
            throw AdminActionException::requirement('Role yang dipilih tidak dikenal.');
        }
    }

    private function assertAnotherActiveSuperAdminExists(User $target): void
    {
        $remaining = User::query()
            ->where('role', 'super_admin')
            ->where('is_active', true)
            ->where('id', '!=', $target->getKey())
            ->lockForUpdate()
            ->pluck('id')
            ->count();

        if ($remaining === 0) {
            throw AdminActionException::requirement(
                'Tidak dapat menghapus super admin aktif terakhir. Aktifkan atau angkat super admin lain terlebih dahulu.',
            );
        }
    }
}
