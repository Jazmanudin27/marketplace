<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\Tenant;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->text('address');
            $table->string('phone');
            $table->text('description');
            $table->string('photo_1')->nullable();
            $table->string('photo_2')->nullable();
            $table->string('photo_3')->nullable();
            $table->string('status')->default('Pending'); // Pending, Diproses, Selesai, Dibatalkan
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        // Buat permission Spatie
        $permission = Permission::firstOrCreate([
            'name' => 'manage-complaints',
            'guard_name' => 'web',
        ]);

        // Berikan permission ke role super-admin, admin, dan owner di semua tenant
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            setPermissionsTeamId($tenant->id);

            // Admin Role
            $adminRole = Role::where('tenant_id', $tenant->id)->where('name', 'admin')->first();
            if ($adminRole) {
                $adminRole->givePermissionTo($permission);
            }

            // Owner Role
            $ownerRole = Role::where('tenant_id', $tenant->id)->where('name', 'owner')->first();
            if ($ownerRole) {
                $ownerRole->givePermissionTo($permission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');

        $permission = Permission::where('name', 'manage-complaints')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
