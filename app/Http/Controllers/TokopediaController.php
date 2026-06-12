<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Store;
use App\Services\TokopediaService;
use App\Jobs\PullProductsFromTokopedia;
use App\Jobs\PullOrdersFromTokopedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TokopediaController extends Controller
{
    protected $tokopediaService;

    public function __construct(TokopediaService $tokopediaService)
    {
        $this->tokopediaService = $tokopediaService;
    }

    /**
     * Menampilkan form untuk menghubungkan Toko Tokopedia
     */
    public function connectForm()
    {
        return view('stores.tokopedia.connect');
    }

    /**
     * Memproses koneksi Toko Tokopedia
     */
    public function connect(Request $request)
    {
        $data = $request->validate([
            'store_name'           => 'required|string|max:255',
            'marketplace_store_id' => 'required|string|max:100',
        ]);

        $tenantId = Auth::user()->tenant_id;

        try {
            // STEP 1: Verifikasi koneksi/token Tokopedia
            $accessToken = $this->tokopediaService->getAccessToken($data['marketplace_store_id']);

            if (empty($accessToken)) {
                return back()->withInput()->with('error', 'Gagal memverifikasi toko Tokopedia. Silakan periksa kredensial Anda.');
            }

            // STEP 2: Pastikan channel tokopedia ada di DB
            Channel::ensureChannelsExist();
            $channel = Channel::where('code', 'tokopedia')->firstOrFail();

            // STEP 3: Simpan atau update toko di database
            $store = Store::updateOrCreate(
                [
                    'tenant_id'            => $tenantId,
                    'channel_id'           => $channel->id,
                    'marketplace_store_id' => $data['marketplace_store_id'],
                ],
                [
                    'store_name'       => $data['store_name'],
                    'access_token'     => $accessToken,
                    'token_expires_at' => now()->addDays(30), // Default token lifetime simulation
                    'status'           => 'connected',
                ]
            );

            Log::info('[Tokopedia Connect] Toko berhasil terhubung', [
                'store_id'   => $store->id,
                'store_name' => $store->store_name,
                'tenant_id'  => $tenantId,
            ]);

            return redirect()->route('stores.index')
                ->with('success', "✅ Toko Tokopedia \"{$store->store_name}\" berhasil terhubung!");

        } catch (\Exception $e) {
            Log::error('[Tokopedia Connect] Gagal menghubungkan toko', [
                'message' => $e->getMessage(),
                'tenant_id' => $tenantId,
            ]);

            return back()->withInput()->with('error', 'Gagal menghubungkan toko Tokopedia: ' . $e->getMessage());
        }
    }

    /**
     * Memicu sinkronisasi produk Tokopedia di latar belakang
     */
    public function syncProducts(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'tokopedia', 400, 'Bukan toko Tokopedia.');
        abort_if($store->status !== 'connected', 400, 'Toko belum terhubung.');

        try {
            PullProductsFromTokopedia::dispatch($store);
            
            return back()->with('success', 'Sinkronisasi produk Tokopedia sedang berjalan di latar belakang.');
        } catch (\Exception $e) {
            Log::error('[Tokopedia Sync Products] Gagal memulai sync', [
                'store_id' => $store->id,
                'error'    => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal memulai sinkronisasi produk: ' . $e->getMessage());
        }
    }

    /**
     * Memicu sinkronisasi pesanan Tokopedia di latar belakang
     */
    public function syncOrders(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'tokopedia', 400, 'Bukan toko Tokopedia.');
        abort_if($store->status !== 'connected', 400, 'Toko belum terhubung.');

        try {
            $timeFrom = now()->subDays(15)->timestamp;
            $timeTo = now()->timestamp;

            PullOrdersFromTokopedia::dispatch($store, $timeFrom, $timeTo);

            return back()->with('success', 'Sinkronisasi pesanan Tokopedia sedang berjalan di latar belakang.');
        } catch (\Exception $e) {
            Log::error('[Tokopedia Sync Orders] Gagal memulai sync', [
                'store_id' => $store->id,
                'error'    => $e->getMessage()
            ]);
            return back()->with('error', 'Gagal memulai sinkronisasi pesanan: ' . $e->getMessage());
        }
    }
}
