<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), Exception::class, 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id'); // permission id
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id'); // role id
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            if ($teams) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        // =====================================================================
        // INISIALISASI DATA PERMISSION & ROLE UNTUK SETIAP TENANT YANG ADA
        // =====================================================================
        $permissions = [
            // Master Data
            'manage-categories',
            'manage-brands',
            'manage-suppliers',
            'manage-employees',
            'manage-customers',
            'manage-users',
            // Products
            'manage-products',
            // Toko
            'manage-stores',
            // Transaksi
            'manage-incoming-goods',
            'manage-orders',
            'manage-fulfillment',
            'manage-returns',
            'manage-offline-sales',
            'manage-chats',
            // Persediaan
            'manage-inventory',
            // Laporan
            'view-warehouse-reports',
            // Keuangan
            'view-financial-reports',
            'manage-finance',
        ];

        // 1. Buat permissions global
        foreach ($permissions as $permissionName) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 2. Setup role & hubungkan user lama untuk setiap tenant
        $tenants = \App\Models\Tenant::all();
        foreach ($tenants as $tenant) {
            // Set global team id untuk pemisahan tenant pada Spatie
            setPermissionsTeamId($tenant->id);

            // Buat role bawaan untuk tenant ini
            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => 'admin',
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $warehouseRole = \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => 'warehouse',
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            $financeRole = \Spatie\Permission\Models\Role::firstOrCreate([
                'name' => 'finance',
                'guard_name' => 'web',
                'tenant_id' => $tenant->id,
            ]);

            // Sinkronisasi Izin untuk masing-masing Role
            $adminRole->syncPermissions($permissions);

            $warehouseRole->syncPermissions([
                'manage-products',
                'manage-incoming-goods',
                'manage-orders',
                'manage-fulfillment',
                'manage-returns',
                'manage-offline-sales',
                'manage-chats',
                'manage-inventory',
            ]);

            $financeRole->syncPermissions([
                'view-financial-reports',
                'manage-finance',
            ]);

            // Petakan user lama yang ada di tenant ini ke role barunya
            $users = \App\Models\User::where('tenant_id', $tenant->id)->get();
            foreach ($users as $user) {
                if ($user->role === 'admin') {
                    $user->assignRole($adminRole);
                } elseif ($user->role === 'warehouse') {
                    $user->assignRole($warehouseRole);
                } elseif ($user->role === 'finance') {
                    $user->assignRole($financeRole);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        throw_if(empty($tableNames), Exception::class, 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
