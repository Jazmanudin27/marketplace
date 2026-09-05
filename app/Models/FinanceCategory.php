<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FinanceCategory extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'code',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Helper to auto-seed default categories for a tenant if none exist yet.
     */
    public static function seedDefaultsForTenant(int $tenantId): void
    {
        if (self::where('tenant_id', $tenantId)->exists()) {
            return;
        }

        $defaultExpenses = [
            ['name' => 'Gaji Karyawan', 'code' => 'salary', 'description' => 'Biaya upah / gaji bulanan atau harian karyawan'],
            ['name' => 'Sewa Tempat', 'code' => 'rent', 'description' => 'Biaya sewa ruko, gudang, atau kantor operasional'],
            ['name' => 'Utilitas & Operasional', 'code' => 'utilities', 'description' => 'Listrik, air, internet, lakban, dan kebutuhan kantor harian'],
            ['name' => 'Bayar Hutang Supplier', 'code' => 'pembelian_supplier', 'description' => 'Pelunasan faktur pembelian bahan baku atau barang dagangan'],
            ['name' => 'Biaya Lain-lain', 'code' => 'other', 'description' => 'Pengeluaran operasional lain di luar pos utama'],
        ];

        foreach ($defaultExpenses as $item) {
            self::create([
                'tenant_id'   => $tenantId,
                'name'        => $item['name'],
                'code'        => $item['code'],
                'type'        => 'expense',
                'description' => $item['description'],
                'is_active'   => true,
            ]);
        }

        $defaultIncomes = [
            ['name' => 'Investasi / Modal', 'code' => 'investment', 'description' => 'Setoran modal pemilik atau suntikan investor'],
            ['name' => 'Refund / Pengembalian', 'code' => 'refund', 'description' => 'Pengembalian dana dari vendor atau pihak ketiga'],
            ['name' => 'Jasa / Layanan', 'code' => 'services', 'description' => 'Pendapatan jasa pengerjaan, servis, atau konsultasi'],
            ['name' => 'Pemasukan Lain-lain', 'code' => 'other', 'description' => 'Pemasukan non-operasional lainnya'],
        ];

        foreach ($defaultIncomes as $item) {
            self::create([
                'tenant_id'   => $tenantId,
                'name'        => $item['name'],
                'code'        => $item['code'],
                'type'        => 'income',
                'description' => $item['description'],
                'is_active'   => true,
            ]);
        }
    }
}
