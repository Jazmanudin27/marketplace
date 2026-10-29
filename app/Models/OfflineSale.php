<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfflineSale extends Model
{
    protected $fillable = [
        'tenant_id', 'store_id', 'user_id', 'customer_id', 'sale_number', 'status',
        'buyer_name', 'buyer_phone', 'institution_name', 'payment_method', 'payment_destination',
        'total_amount', 'discount_amount', 'discount_type', 'discount_value', 'grand_total',
        'paid_amount', 'change_amount', 'notes', 'sold_at',
        'is_dropship', 'dropshipper_name', 'dropshipper_phone', 'resi_number', 'resi_file', 'is_po', 'follow_up_date',
        'approved_by', 'approved_at',
        'cancellation_reason', 'cancelled_by',
        'packing_status', 'packed_at', 'packed_by',
    ];

    protected $casts = [
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_value'  => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'change_amount'   => 'decimal:2',
        'sold_at'         => 'datetime',
        'packed_at'       => 'datetime',
        'follow_up_date'  => 'date',
        'is_dropship'     => 'boolean',
        'is_po'           => 'boolean',
    ];

    const STATUS_COMPLETED        = 'completed';
    const STATUS_CANCELLED        = 'cancelled';
    const STATUS_PENDING          = 'pending';
    const STATUS_WAITING_DP       = 'menunggu_dp';
    const STATUS_PENDING_SPK      = 'belum_spk';
    const STATUS_SPK_PROCESSING   = 'spk_diproses';
    const STATUS_PENDING_APPROVAL = 'pending_approval';

    const PAYMENT_METHODS = [
        'tunai'    => 'Tunai',
        'transfer' => 'Transfer Bank',
        'qris'     => 'QRIS',
        'piutang'  => 'Kredit (Tempo / Bayar Nanti)',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OfflineSaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OfflineSalePayment::class)->orderBy('payment_date')->orderBy('id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OfflineSaleReturn::class);
    }

    public function spks(): HasMany
    {
        return $this->hasMany(Spk::class, 'no_pesanan', 'sale_number');
    }

    public function activeSpks(): HasMany
    {
        return $this->hasMany(Spk::class, 'no_pesanan', 'sale_number')->where('is_batal', false);
    }


    public function packer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'packed_by');
    }

    public function getPackingStatusLabelAttribute(): string
    {
        return match ($this->packing_status) {
            'verified', 'packed' => 'Sudah Dikemas',
            'packing'            => 'Sedang Dikemas',
            default              => 'Belum Dikemas',
        };
    }

    public function getPackingBadgeClassAttribute(): string
    {
        return match ($this->packing_status) {
            'verified', 'packed' => 'bg-success text-white',
            'packing'            => 'bg-info text-dark',
            default              => 'bg-secondary text-white',
        };
    }

    public function getTotalReturnedAmountAttribute(): float
    {
        return (float) $this->returns()->sum('total_return_amount');
    }

    public function getIsReturnedAttribute(): bool
    {
        return $this->returns()->exists();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED        => 'success',
            self::STATUS_CANCELLED        => 'danger',
            self::STATUS_WAITING_DP       => 'info',
            self::STATUS_PENDING_SPK      => 'warning',
            self::STATUS_SPK_PROCESSING   => 'primary',
            self::STATUS_PENDING_APPROVAL => $this->is_po ? 'primary' : 'success',
            default                       => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED        => 'Selesai',
            self::STATUS_CANCELLED        => 'Dibatalkan',
            self::STATUS_WAITING_DP       => 'Menunggu DP Masuk',
            self::STATUS_PENDING_SPK      => 'Belum dibuat SPK',
            self::STATUS_SPK_PROCESSING   => 'SPK Sedang Diproses',
            self::STATUS_PENDING_APPROVAL => $this->is_po ? 'SPK Sedang Diproses' : 'Selesai',
            default                       => ucfirst($this->status),
        };
    }

    /**
     * Cek apakah pesanan PO membutuhkan follow up (lewat tanggal follow up dan DP belum masuk).
     */
    public function getNeedsFollowUpAttribute(): bool
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED])) {
            return false;
        }

        if ((float) $this->paid_amount > 0) {
            return false;
        }

        if (!$this->follow_up_date) {
            return false;
        }

        return now()->startOfDay()->gt($this->follow_up_date->startOfDay());
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst($this->payment_method);
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        if ($this->payment_method === 'piutang') {
            if ($this->is_paid) {
                return 'Kredit (Lunas)';
            }
            if ((float) $this->paid_amount > 0) {
                return 'Kredit (Dicicil)';
            }
            return 'Kredit';
        }

        if ($this->is_paid) {
            return 'Tunai';
        }

        if ((float) $this->paid_amount > 0) {
            return 'Tunai (Dicicil)';
        }

        return 'Tunai (Belum Lunas)';
    }

    public function getPaymentTypeBadgeAttribute(): string
    {
        if ($this->payment_method === 'piutang') {
            if ($this->is_paid) {
                return 'success';
            }
            if ((float) $this->paid_amount > 0) {
                return 'warning text-dark';
            }
            return 'danger';
        }

        if ($this->is_paid) {
            return 'success';
        }

        if ((float) $this->paid_amount > 0) {
            return 'warning text-dark';
        }

        return 'danger';
    }

    /**
     * Sisa tagihan yang belum dibayar.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->grand_total - (float) $this->paid_amount);
    }

    /**
     * Persentase tagihan yang sudah terbayar (0 - 100%).
     */
    public function getPaymentPercentageAttribute(): float
    {
        if ((float) $this->grand_total <= 0) {
            return 100.0;
        }
        return min(100.0, round(((float) $this->paid_amount / (float) $this->grand_total) * 100, 1));
    }

    /**
     * Cek apakah pembayaran sudah Lunas.
     */
    public function getIsPaidAttribute(): bool
    {
        return (float) $this->paid_amount >= (float) $this->grand_total && (float) $this->grand_total > 0;
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'Dibatalkan';
        }
        if ($this->is_paid) {
            return 'Lunas';
        }
        if ((float) $this->paid_amount > 0) {
            return 'Dicicil';
        }
        return 'Belum Lunas';
    }

    public function getPaymentStatusBadgeAttribute(): string
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return 'secondary';
        }
        if ($this->is_paid) {
            return 'success';
        }
        if ((float) $this->paid_amount > 0) {
            return 'warning';
        }
        return 'danger';
    }

    /**
     * Generate nomor penjualan otomatis: OS-YYYYMMDD-XXXX
     */
    public static function generateSaleNumber(): string
    {
        $date   = now()->format('Ymd');
        $prefix = 'OS-' . $date . '-';
        $last   = static::where('sale_number', 'like', $prefix . '%')
                        ->orderByDesc('id')
                        ->value('sale_number');
        $seq = $last ? ((int) substr($last, -4) + 1) : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Total HPP seluruh item penjualan offline.
     */
    public function getHppTotalAttribute(): float
    {
        if (in_array(strtolower((string) $this->status), ['cancelled', 'batal', 'returned', 'retur'])) {
            return 0.0;
        }
        $total = 0.0;
        foreach ($this->items as $item) {
            if ($item->masterProduct) {
                $total += (float) $item->masterProduct->cost_price * $item->quantity;
            }
        }
        return $total;
    }

    /**
     * Net Profit = Pendapatan Bersih (Grand Total) - HPP
     */
    public function getNetProfitAttribute(): float
    {
        return (float) $this->grand_total - $this->hpp_total;
    }

    protected static $bankAccountCache = [];

    /**
     * Label akun kas / bank penerima sesuai master database
     */
    public function getPaymentDestinationLabelAttribute(): string
    {
        $val = $this->payment_destination;
        if ($val) {
            $cacheKey = ($this->tenant_id ?? 0) . '_' . $val;
            if (isset(static::$bankAccountCache[$cacheKey])) {
                return static::$bankAccountCache[$cacheKey];
            }

            $bank = BankAccount::where('tenant_id', $this->tenant_id)
                ->where(function ($q) use ($val) {
                    $q->where('bank_name', $val)->orWhere('id', $val);
                })->first();

            if ($bank) {
                $label = $bank->bank_name;
                if ($bank->account_number) {
                    $label .= ' (' . $bank->account_number . ')';
                }
                if ($bank->account_name) {
                    $label .= ' - ' . $bank->account_name;
                }
                return static::$bankAccountCache[$cacheKey] = $label;
            }

            if ($val === 'kas_kecil') {
                return static::$bankAccountCache[$cacheKey] = 'Kas Kecil';
            }
            if ($val === 'kas_besar') {
                return static::$bankAccountCache[$cacheKey] = 'Kas Besar';
            }

            return static::$bankAccountCache[$cacheKey] = ucwords(str_replace('_', ' ', $val));
        }

        if ($this->payment_method === 'tunai') {
            return 'Kas Tunai';
        }

        return 'Non-Tunai (' . strtoupper($this->payment_method ?? 'POS') . ')';
    }

    /**
     * Sinkronisasi perubahan produk & data pesanan ke SPK Produksi terkait.
     */
    public function syncSpks(): void
    {
        $this->unsetRelation('spks');
        $this->unsetRelation('items');

        $saleNumber = trim((string) $this->sale_number);
        $cleanNumber = ltrim($saleNumber, '#');

        $spks = Spk::where('tenant_id', $this->tenant_id)
            ->where(function ($q) use ($saleNumber, $cleanNumber) {
                $q->where('no_pesanan', $saleNumber)
                  ->orWhere('no_pesanan', '#' . $cleanNumber)
                  ->orWhere('no_pesanan', $cleanNumber);
            })
            ->with(['items'])
            ->get();

        if ($spks->isEmpty()) {
            return;
        }

        // Standarisasi nomor pesanan pada SPK
        foreach ($spks as $spk) {
            if ($spk->no_pesanan !== $saleNumber) {
                $spk->update(['no_pesanan' => $saleNumber]);
            }
        }

        $activeSpks = $spks->where('is_batal', false);
        $primarySpk = $activeSpks->first() ?? $spks->first();

        $saleItems = $this->items()->get();

        // 1. Jika SPK utama tadinya batal tapi sekarang ada item di penjualan, aktifkan kembali
        if ($primarySpk && $primarySpk->is_batal && $saleItems->isNotEmpty()) {
            $primarySpk->update([
                'is_batal'       => false,
                'alasan_batal'   => null,
                'batal_at'       => null,
                'batal_by'       => null,
                'tahap_saat_ini' => 'Antrian & Sampling',
            ]);
            $activeSpks = $spks->fresh()->where('is_batal', false);
        }

        // 2. Perbarui info pemesan & instansi pada semua SPK aktif
        foreach ($activeSpks as $spk) {
            $spk->update([
                'pemesan'       => $this->buyer_name ?: 'Pelanggan PO',
                'no_hp_pemesan' => $this->buyer_phone ?: '',
                'instansi'      => $this->institution_name ?: ('Penjualan PO #' . $saleNumber),
            ]);
        }

        // 3. Sinkronisasi rincian produk ke SpkItem
        $allSpkItems = $spks->flatMap->items;
        $usedSpkItemIds = [];
        $unmatchedSaleItems = [];

        // Pass 1: Cocokkan item yang identik (master_product_id / SKU / nama produk yang sama)
        foreach ($saleItems as $item) {
            $masterProductId = $item->master_product_id;
            $productName     = $item->product_name;
            $sku             = $item->sku;
            $qty             = (int) $item->quantity;
            $unitPrice       = (float) $item->unit_price;

            $ukuran = null;
            if (preg_match('/\b(XS|S|M|L|XL|XXL|XXXL|2XL|3XL|4XL|5XL)\b/i', (string) $productName, $m)) {
                $ukuran = strtoupper($m[1]);
            }

            $estKainVal = 0;
            if (!empty($masterProductId)) {
                $mp = MasterProduct::find($masterProductId);
                if ($mp && $mp->est_kain > 0) {
                    $estKainVal = (float) $mp->est_kain * $qty;
                }
            }

            // Cari SpkItem yang cocok berdasarkan master_product_id
            $matchedSpkItem = null;
            if (!empty($masterProductId)) {
                $matchedSpkItem = $allSpkItems->first(function ($si) use ($masterProductId, $usedSpkItemIds) {
                    return !in_array($si->id, $usedSpkItemIds) && (int) $si->master_product_id === (int) $masterProductId;
                });
            }

            // Fallback match berdasarkan SKU jika belum cocok
            if (!$matchedSpkItem && !empty($sku)) {
                $matchedSpkItem = $allSpkItems->first(function ($si) use ($sku, $usedSpkItemIds) {
                    return !in_array($si->id, $usedSpkItemIds) && !empty($si->sku) && strtolower(trim((string) $si->sku)) === strtolower(trim((string) $sku));
                });
            }

            // Fallback match berdasarkan nama produk jika belum cocok
            if (!$matchedSpkItem && !empty($productName)) {
                $matchedSpkItem = $allSpkItems->first(function ($si) use ($productName, $usedSpkItemIds) {
                    return !in_array($si->id, $usedSpkItemIds) && trim((string) $si->nama_produk) === trim((string) $productName);
                });
            }

            if ($matchedSpkItem) {
                $matchedSpkItem->update([
                    'master_product_id' => $masterProductId,
                    'nama_produk'       => $productName,
                    'sku'               => $sku,
                    'ukuran'            => $ukuran ?: $matchedSpkItem->ukuran,
                    'quantity'          => $qty,
                    'est_kain'          => $estKainVal > 0 ? $estKainVal : $matchedSpkItem->est_kain,
                    'hpp'               => $unitPrice,
                ]);
                $usedSpkItemIds[] = $matchedSpkItem->id;
            } else {
                $unmatchedSaleItems[] = [
                    'item'       => $item,
                    'ukuran'     => $ukuran,
                    'estKainVal' => $estKainVal,
                ];
            }
        }

        // Pass 2: Jika ada item produk yang diganti/diedit di keranjang penjualan (misal ganti dari produk A ke B),
        // update SpkItem lama yang tersisa secara in-place agar histori proses & tracking tetap terjaga
        $availableSpkItems = $allSpkItems->filter(fn($si) => !in_array($si->id, $usedSpkItemIds))->values();
        $newItemsToCreate = [];

        foreach ($unmatchedSaleItems as $unmatched) {
            $item            = $unmatched['item'];
            $ukuran          = $unmatched['ukuran'];
            $estKainVal      = $unmatched['estKainVal'];
            $masterProductId = $item->master_product_id;
            $productName     = $item->product_name;
            $sku             = $item->sku;
            $qty             = (int) $item->quantity;
            $unitPrice       = (float) $item->unit_price;

            if ($availableSpkItems->isNotEmpty()) {
                $spkItemToReuse = $availableSpkItems->shift();
                $spkItemToReuse->update([
                    'master_product_id' => $masterProductId,
                    'nama_produk'       => $productName,
                    'sku'               => $sku,
                    'ukuran'            => $ukuran ?: $spkItemToReuse->ukuran,
                    'quantity'          => $qty,
                    'est_kain'          => $estKainVal > 0 ? $estKainVal : $spkItemToReuse->est_kain,
                    'hpp'               => $unitPrice,
                ]);
                $usedSpkItemIds[] = $spkItemToReuse->id;
            } else {
                $newItemsToCreate[] = $unmatched;
            }
        }

        // Pass 3: Jika item penjualan bertambah melebihi SpkItem yang ada, buat SpkItem baru
        foreach ($newItemsToCreate as $newItem) {
            $item            = $newItem['item'];
            $ukuran          = $newItem['ukuran'];
            $estKainVal      = $newItem['estKainVal'];
            $masterProductId = $item->master_product_id;
            $productName     = $item->product_name;
            $sku             = $item->sku;
            $qty             = (int) $item->quantity;
            $unitPrice       = (float) $item->unit_price;

            $targetSpk = $activeSpks->first() ?? $spks->first();
            if ($targetSpk) {
                $createdItem = SpkItem::create([
                    'spk_id'            => $targetSpk->id,
                    'master_product_id' => $masterProductId,
                    'nama_produk'       => $productName,
                    'sku'               => $sku,
                    'ukuran'            => $ukuran,
                    'quantity'          => $qty,
                    'est_kain'          => $estKainVal,
                    'hpp'               => $unitPrice,
                    'status'            => 'Pending',
                ]);
                $usedSpkItemIds[] = $createdItem->id;
            }
        }

        // Pass 4: Hapus SpkItem yang tidak lagi ada di keranjang penjualan offline
        $unmatchedSpkItems = $allSpkItems->filter(function ($si) use ($usedSpkItemIds) {
            return !in_array($si->id, $usedSpkItemIds);
        });

        foreach ($unmatchedSpkItems as $removedItem) {
            $removedItem->delete();
        }

        // Pass 5: Cek ulang kategori & status SPK setelah seluruh item diperbarui
        foreach ($spks as $spk) {
            $spk->unsetRelation('items');
            $currentItems = $spk->items()->get();
            $remainingCount = $currentItems->count();

            if ($remainingCount === 0 && !$spk->is_batal) {
                $spk->update([
                    'is_batal'       => true,
                    'alasan_batal'   => 'Semua item pesanan dihapus pada revisi Penjualan Offline #' . $saleNumber,
                    'batal_at'       => now(),
                    'batal_by'       => \Illuminate\Support\Facades\Auth::id(),
                    'tahap_saat_ini' => 'Batal',
                ]);
            } elseif ($remainingCount > 0) {
                $firstItemName = (string) ($currentItems->first()->nama_produk ?? 'Produk SPK');
                $newKategori = ($remainingCount === 1)
                    ? $firstItemName
                    : ($spk->kategori ?: $firstItemName);

                $spk->update([
                    'kategori' => mb_substr(trim((string) $newKategori), 0, 50),
                ]);
            }
        }
    }
}

