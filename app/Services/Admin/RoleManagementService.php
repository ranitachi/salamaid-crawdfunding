<?php

namespace App\Services\Admin;

use App\Exceptions\AdminActionException;
use App\Models\User;

class RoleManagementService
{
    /**
     * Ringkasan role dari config/admin.php beserta jumlah pemakainya.
     *
     * @return array<int, array{role: string, label: string, users: int, active: int, permissions: array<int, string>}>
     */
    public function usage(): array
    {
        $roles = (array) config('admin.roles', []);
        $counts = User::query()
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');
        $activeCounts = User::query()
            ->where('is_active', true)
            ->selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        $rows = [];

        foreach ($roles as $role => $label) {
            $rows[] = [
                'role' => $role,
                'label' => $label,
                'users' => (int) ($counts[$role] ?? 0),
                'active' => (int) ($activeCounts[$role] ?? 0),
                'permissions' => (array) config("admin.permissions.{$role}", []),
            ];
        }

        return $rows;
    }

    /**
     * Role didefinisikan di config/admin.php, jadi tidak ada role yang dapat
     * dihapus dari panel. Guard ini memastikan role yang masih dipakai tidak
     * pernah dapat dihapus dan memberi pesan yang jelas untuk role kosong.
     */
    public function delete(string $role, User $actor): void
    {
        if (! array_key_exists($role, (array) config('admin.roles', []))) {
            throw AdminActionException::requirement('Role tidak ditemukan.');
        }

        $count = User::query()->where('role', $role)->count();

        if ($count > 0) {
            throw AdminActionException::requirement(sprintf(
                'Role "%s" masih dipakai oleh %d user dan tidak dapat dihapus.',
                $role,
                $count,
            ));
        }

        throw AdminActionException::requirement(
            'Role didefinisikan di config/admin.php dan tidak dapat dihapus dari panel admin.',
        );
    }
}
