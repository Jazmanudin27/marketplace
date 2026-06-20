<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Tenant;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First create the permissions if they don't exist yet
        $attendancePermissions = [
            'view-attendance',
            'propose-attendance-correction',
            'approve-attendance-correction',
            'print-attendance-report',
        ];

        foreach ($attendancePermissions as $pName) {
            Permission::firstOrCreate([
                'name' => $pName,
                'guard_name' => 'web',
            ]);
        }

        // Setup owner role for all existing tenants
        $tenants = Tenant::all();
        $permissions = Permission::all();

        foreach ($tenants as $tenant) {
            setPermissionsTeamId($tenant->id);

            $ownerRole = Role::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'owner',
                'guard_name' => 'web',
            ]);

            // Sync all permissions to owner role
            $ownerRole->syncPermissions($permissions);

            // Sync all permissions EXCEPT approve-attendance-correction to admin role
            $adminRole = Role::where('tenant_id', $tenant->id)->where('name', 'admin')->first();
            if ($adminRole) {
                $adminPermissions = Permission::where('name', '!=', 'approve-attendance-correction')->get();
                $adminRole->syncPermissions($adminPermissions);
            }

            // Assign Spatie role to users with user.role = 'owner'
            $users = User::where('tenant_id', $tenant->id)
                ->where('role', 'owner')
                ->get();

            foreach ($users as $user) {
                $user->assignRole($ownerRole);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            setPermissionsTeamId($tenant->id);
            $role = Role::where('tenant_id', $tenant->id)->where('name', 'owner')->first();
            if ($role) {
                $role->delete();
            }
        }
    }
};
