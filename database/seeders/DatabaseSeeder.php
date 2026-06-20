<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ChannelSeeder::class,        // 1. Channels (dibutuhkan TenantSeeder)
            TenantSeeder::class,         // 2. Tenant & user demo
            RolePermissionSeeder::class, // 3. Roles, permissions, user demo baru
            FaqSeeder::class,            // 4. FAQ & tutorial
            SupplierSeeder::class,       // 5. Data supplier demo
        ]);
    }
}
