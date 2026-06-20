<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $tenantA = Tenant::where('name', 'Perusahaan A (Demo)')->first();
        $tenantB = Tenant::where('name', 'Perusahaan B (Demo)')->first();

        // ==========================================
        // Supplier untuk Perusahaan A
        // ==========================================
        if ($tenantA) {
            $suppliersA = [
                [
                    'name'           => 'PT. Maju Bersama Tekstil',
                    'contact_person' => 'Budi Santoso',
                    'phone'          => '0812-3456-7890',
                    'address'        => 'Jl. Industri Raya No. 12, Kawasan Industri Pulogadung, Jakarta Timur 13930',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'CV. Sejahtera Abadi Fashion',
                    'contact_person' => 'Siti Rahayu',
                    'phone'          => '0821-9876-5432',
                    'address'        => 'Jl. Cimahi Selatan No. 45, Bandung, Jawa Barat 40535',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'Toko Grosir Sandang Jaya',
                    'contact_person' => 'Ahmad Fauzi',
                    'phone'          => '0857-1234-5678',
                    'address'        => 'Pasar Tanah Abang Blok B No. 23, Jakarta Pusat 10710',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'PT. Global Distribusi Nusantara',
                    'contact_person' => 'Rina Kusuma',
                    'phone'          => '021-5555-1234',
                    'address'        => 'Gedung Menara Sudirman Lt. 8, Jl. Jend. Sudirman Kav. 60, Jakarta Selatan 12190',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'UD. Karya Mandiri Shoes',
                    'contact_person' => 'Dani Prasetyo',
                    'phone'          => '0878-8765-4321',
                    'address'        => 'Sentra Sepatu Cibaduyut No. 88, Bandung, Jawa Barat 40239',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'PT. Indo Jaya Packaging',
                    'contact_person' => 'Dewi Lestari',
                    'phone'          => '0815-7654-3210',
                    'address'        => 'Jl. Raya Bekasi Km 28, Cikarang Utara, Bekasi, Jawa Barat 17530',
                    'is_active'      => false,
                ],
            ];

            foreach ($suppliersA as $supplier) {
                Supplier::create(array_merge($supplier, ['tenant_id' => $tenantA->id]));
            }
        }

        // ==========================================
        // Supplier untuk Perusahaan B
        // ==========================================
        if ($tenantB) {
            $suppliersB = [
                [
                    'name'           => 'PT. Nusantara Elektronik Utama',
                    'contact_person' => 'Hendra Wijaya',
                    'phone'          => '0813-2233-4455',
                    'address'        => 'Jl. Gunung Sahari Raya No. 78, Pademangan, Jakarta Utara 14420',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'CV. Surya Mandiri Supplier',
                    'contact_person' => 'Tini Wulandari',
                    'phone'          => '0896-3344-5566',
                    'address'        => 'Jl. Pahlawan No. 15, Surabaya, Jawa Timur 60174',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'Importir Barang Asia Pacific',
                    'contact_person' => 'Kevin Tanaka',
                    'phone'          => '021-7788-9900',
                    'address'        => 'Jl. Mangga Dua Raya Blok C No. 5, Sawah Besar, Jakarta Pusat 10730',
                    'is_active'      => true,
                ],
                [
                    'name'           => 'PT. Sentral Logistik Indonesia',
                    'contact_person' => 'Farida Nurhasanah',
                    'phone'          => '0819-6677-8899',
                    'address'        => 'Jl. Raya Mastrip No. 200, Karang Pilang, Surabaya 60221',
                    'is_active'      => false,
                ],
            ];

            foreach ($suppliersB as $supplier) {
                Supplier::create(array_merge($supplier, ['tenant_id' => $tenantB->id]));
            }
        }
    }
}
