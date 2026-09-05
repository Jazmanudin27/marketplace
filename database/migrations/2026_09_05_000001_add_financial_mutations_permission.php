<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // 1. Create permission if not exists
            $perm = Permission::firstOrCreate([
                'name'       => 'finance.mutations.index',
                'guard_name' => 'web',
            ]);

            // 2. Automatically assign to admin & owner roles across all tenants
            $rolesToAssign = Role::whereIn('name', ['admin', 'owner', 'super-admin', 'finance', 'keuangan', 'akuntan'])->get();
            foreach ($rolesToAssign as $role) {
                if (!$role->hasPermissionTo('finance.mutations.index')) {
                    $role->givePermissionTo($perm);
                }
            }

            // 3. Also assign to any role that currently has financial permissions
            $financeRoles = Role::whereHas('permissions', function ($q) {
                $q->whereIn('name', ['view-financial-reports', 'manage-finance', 'finance.profit_loss', 'finance.profit-loss.index']);
            })->get();

            foreach ($financeRoles as $role) {
                if (!$role->hasPermissionTo('finance.mutations.index')) {
                    $role->givePermissionTo($perm);
                }
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Silently continue if permission tables are not ready in certain environments
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            Permission::where('name', 'finance.mutations.index')
                ->where('guard_name', 'web')
                ->delete();
        } catch (\Throwable $e) {
            //
        }
    }
};
