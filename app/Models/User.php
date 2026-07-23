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
    use HasFactory, Notifiable;
    use HasRoles {
        hasPermissionTo as traitHasPermissionTo;
    }

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
            try {
                $session = app('session');
                if (!$session->has('selected_tenant_id')) {
                    $selectedTenantId = $value;
                    if (!$selectedTenantId) {
                        $firstTenant = Tenant::first();
                        $selectedTenantId = $firstTenant ? $firstTenant->id : null;
                    }
                    if ($selectedTenantId) {
                        $session->put('selected_tenant_id', $selectedTenantId);
                    }
                    return $selectedTenantId;
                }
                return $session->get('selected_tenant_id');
            } catch (\Exception $e) {
                return $value;
            }
        }
        return $value;
    }

    public function getTenantAttribute()
    {
        if ($this->role === 'super-admin') {
            $selectedTenantId = $this->tenant_id;
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
        return $this->role === 'super-admin';
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

    /**
     * Override hasPermissionTo to bridge legacy module permissions and granular permissions.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        // 1. Check if user directly or via role has the requested permission
        try {
            if ($this->traitHasPermissionTo($permission, $guardName)) {
                return true;
            }
        } catch (\Exception $e) {
            // Ignore if permission doesn't exist in database
        }

        // 2. Define legacy module-level permissions and their child granular permissions mapping
        $mapping = [
            'manage-categories' => ['categories.index', 'categories.create', 'categories.edit', 'categories.destroy'],
            'manage-brands' => ['brands.index', 'brands.create', 'brands.edit', 'brands.destroy'],
            'manage-suppliers' => ['suppliers.index', 'suppliers.create', 'suppliers.edit', 'suppliers.destroy'],
            'manage-employees' => [
                'employees.index', 'employees.create', 'employees.edit', 'employees.destroy', 'employees.salary',
                'attendance.index', 'attendance.create', 'attendance.edit', 'attendance.destroy', 'attendance.report', 'attendance.print',
                'attendance-corrections.propose', 'attendance-corrections.approve',
                'overtime.index', 'overtime.create', 'overtime.edit', 'overtime.destroy', 'overtime.approve',
                'leave-requests.index', 'leave-requests.create', 'leave-requests.edit', 'leave-requests.destroy', 'leave-requests.approve',
                'cash-advances.index', 'cash-advances.create', 'cash-advances.edit', 'cash-advances.destroy', 'cash-advances.approve',
                'payroll.index', 'payroll.show', 'payroll.generate', 'payroll.edit', 'payroll.pay', 'payroll.print', 'payroll.destroy',
                'holidays.index', 'holidays.create', 'holidays.edit', 'holidays.destroy',
                'allowance-types.index', 'allowance-types.create', 'allowance-types.edit', 'allowance-types.destroy',
                'late-penalties.index', 'late-penalties.create', 'late-penalties.edit', 'late-penalties.destroy'
            ],
            'manage-customers' => ['customers.index', 'customers.show', 'customers.create', 'customers.edit', 'customers.destroy'],
            'manage-users' => ['users.index', 'users.create', 'users.edit', 'users.destroy', 'roles.index', 'roles.create', 'roles.edit', 'roles.destroy'],
            'manage-products' => [
                'products.index', 'products.show', 'products.create', 'products.edit', 'products.destroy', 'products.publish', 'products.export',
                'marketplace-products.index', 'marketplace-products.link', 'marketplace-products.settings', 'marketplace-products.promote'
            ],
            'manage-stores' => ['stores.index', 'stores.create', 'stores.edit', 'stores.destroy', 'stores.sync'],
            'manage-incoming-goods' => ['incoming-goods.index', 'incoming-goods.create', 'purchase-orders.index', 'purchase-orders.create', 'purchase-orders.edit', 'purchase-orders.destroy', 'purchase-orders.report'],
            'manage-orders' => ['orders.index', 'orders.show', 'orders.create', 'orders.process', 'orders.ship', 'orders.print', 'orders.export', 'orders.sync'],
            'manage-fulfillment' => ['fulfillment.index', 'fulfillment.scan', 'fulfillment.complete'],
            'manage-returns' => ['returns.index', 'returns.sync', 'returns.restock'],
            'manage-offline-sales' => ['offline-sales.index', 'offline-sales.show', 'offline-sales.create', 'offline-sales.approve', 'offline-sales.complete', 'offline-sales.cancel', 'offline-sales.print'],
            'manage-chats' => ['chats.index', 'chats.show', 'chats.reply', 'chats.sync'],
            'manage-inventory' => [
                'inventory.index', 'inventory.ledger', 'inventory.adjust', 'inventory.stock_sync', 'stock-opnames.index', 'stock-opnames.create',
                'goods-receipts.index', 'goods-receipts.create', 'goods-receipts.edit', 'goods-receipts.destroy',
                'goods-issues.index', 'goods-issues.create', 'goods-issues.edit', 'goods-issues.destroy',
                'purchase-returns.index', 'purchase-returns.create', 'purchase-returns.edit', 'purchase-returns.destroy',
                'pembelian.stock_report', 'pembelian.report_mutation', 'pembelian.report_summary', 'pembelian.stock_card'
            ],
            'view-warehouse-reports' => ['reports.summary', 'reports.summary.print', 'reports.stock', 'reports.stock.print', 'reports.ledger', 'reports.ledger.print', 'reports.opname', 'reports.opname.print', 'reports.analytics', 'reports.master_product', 'reports.production_hpp', 'reports.production_hpp.print'],
            'view-financial-reports' => ['profit.index', 'profit.margin', 'finance.profit-loss.index', 'reports.product_margins', 'reports.store_sales', 'reports.reseller_receivables', 'reports.inventory_turnover'],
            'manage-finance' => ['finance.incomes.index', 'finance.incomes.create', 'finance.incomes.edit', 'finance.incomes.destroy', 'finance.expenses.index', 'finance.expenses.create', 'finance.expenses.edit', 'finance.expenses.destroy', 'finance.transfers.index', 'finance.transfers.create', 'finance.transfers.edit', 'finance.transfers.destroy', 'finance.reconciliation.index'],
            'view-attendance' => ['attendance.index', 'attendance.report'],
            'propose-attendance-correction' => ['attendance-corrections.propose'],
            'approve-attendance-correction' => ['attendance-corrections.approve'],
            'approve-attendance-corrections' => ['attendance-corrections.approve'],
            'print-attendance-report' => ['attendance.print'],
        ];

        // 3. Resolve parent to child permissions checks
        $permName = is_string($permission) ? $permission : ($permission->name ?? null);
        if ($permName && isset($mapping[$permName])) {
            foreach ($mapping[$permName] as $sub) {
                try {
                    if ($this->traitHasPermissionTo($sub, $guardName)) {
                        return true;
                    }
                } catch (\Exception $e) {
                    // Ignore if sub-permission doesn't exist
                }
            }
        }

        return false;
    }
}
