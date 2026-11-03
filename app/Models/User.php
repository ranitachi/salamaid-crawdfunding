<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    /**
     * Active user whose role is one of the admin roles in config/admin.php.
     */
    public function isAdmin(): bool
    {
        return $this->is_active
            && array_key_exists((string) $this->role, config('admin.roles', []));
    }

    /**
     * Check an admin permission against config/admin.php ('*' grants everything).
     */
    public function hasAdminPermission(string $permission): bool
    {
        if (! $this->isAdmin()) {
            return false;
        }

        $granted = config("admin.permissions.{$this->role}", []);

        return in_array('*', $granted, true) || in_array($permission, $granted, true);
    }

    public function hasAnyAdminPermission(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasAdminPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    public function organizationMemberships()
    {
        return $this->hasMany(\Modules\Core\Entities\OrganizationMember::class);
    }

    /** @return array<int, string> */
    public function managedOrganizationIds(): array
    {
        return $this->organizationMemberships()->pluck('organization_id')->map(fn ($id): string => (string) $id)->all();
    }

    public function isOrganizationAdmin(): bool
    {
        return $this->role === 'organization_admin';
    }
}
