<?php

namespace App\Support;

use App\Models\User;

class AdminPermissions
{
    /**
     * Cek permission berdasarkan matrix di config/admin.php.
     *
     * Permission granular per aksi (mis. campaigns.publish) belum tersedia
     * pada A0. Untuk sementara aksi review memakai permission area:
     * - organizations.review
     * - campaigns.review
     */
    public static function allowed(?User $user, string $permission): bool
    {
        if (! $user || ! $user->is_active) {
            return false;
        }

        $permissions = (array) config("admin.permissions.{$user->role}", []);

        return in_array('*', $permissions, true)
            || in_array($permission, $permissions, true);
    }

    /**
     * @param array<int, string> $permissions
     */
    public static function allowedAny(?User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::allowed($user, $permission)) {
                return true;
            }
        }

        return false;
    }
}
