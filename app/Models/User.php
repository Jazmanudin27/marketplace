<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int         $id
 * @property int|null    $tenant_id
 * @property string      $name
 * @property string      $email
 * @property string      $password
 * @property string|null $role
 *
 * @mixin \Spatie\Permission\Traits\HasRoles
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function getTenantIdAttribute($value)
    {
        if ($this->role === 'super-admin') {
            return session('selected_tenant_id', $value);
        }
        return $value;
    }

    public function getTenantAttribute()
    {
        if ($this->role === 'super-admin') {
            $selectedTenantId = session('selected_tenant_id');
            if ($selectedTenantId) {
                $loaded = $this->getRelationValue('tenant');
                if ($loaded && $loaded->id == $selectedTenantId) {
                    return $loaded;
                }
                $tenant = Tenant::find($selectedTenantId);
                $this->setRelation('tenant', $tenant);
                return $tenant;
            }
        }
        return $this->getRelationValue('tenant') ?? $this->tenant()->getResults();
    }

    // =========================================================================
    // Role / Permission Helpers
    // =========================================================================

    /**
     * Cek apakah user adalah Super Admin (bisa akses semua perusahaan).
     *
     * Super Admin diidentifikasi via kolom users.role = 'super-admin'.
     * Super Admin ditempatkan di tenant "__system__" khusus (untuk memenuhi FK constraint).
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super-admin' || ($this->attributes['tenant_id'] ?? null) == 1;
    }

    /**
     * Cek apakah user adalah Owner di tenant-nya.
     */
    public function isOwner(): bool
    {
        if ($this->tenant_id) {
            setPermissionsTeamId($this->tenant_id);
        }
        return $this->hasRole('owner');
    }

    /**
     * Cek apakah user punya role Admin (termasuk owner & super-admin).
     */
    public function isAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if ($this->tenant_id) {
            setPermissionsTeamId($this->tenant_id);
        }
        return $this->hasAnyRole(['admin', 'owner']);
    }

    /**
     * Cek permission dengan otomatis set team context berdasarkan tenant user.
     * Gunakan ini di controller / policy agar tidak perlu set setPermissionsTeamId() manual.
     *
     * Super Admin (tenant_id = 0) selalu return true tanpa cek permission.
     *
     * @param string|array<string> $permission
     */
    public function canDo(string|array $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->tenant_id && $this->tenant_id > 0) {
            setPermissionsTeamId($this->tenant_id);
        }

        $permissions = is_array($permission) ? $permission : [$permission];

        foreach ($permissions as $perm) {
            if ($this->hasPermissionTo($perm)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah user punya SEMUA permission yang diberikan.
     *
     * @param array<string> $permissions
     */
    public function canDoAll(array $permissions): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->tenant_id && $this->tenant_id > 0) {
            setPermissionsTeamId($this->tenant_id);
        }

        foreach ($permissions as $perm) {
            if (! $this->hasPermissionTo($perm)) {
                return false;
            }
        }

        return true;
    }
}
