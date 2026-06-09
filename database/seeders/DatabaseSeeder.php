<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ChannelSeeder::class, // Harus pertama (channels dibutuhkan oleh TenantSeeder)
            TenantSeeder::class,
        ]);
    }
}
