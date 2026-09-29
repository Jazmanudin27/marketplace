<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TokoController extends Controller
{
    public function index()
    {
        Channel::ensureChannelsExist();
        $tenantId = Auth::user()->tenant_id ?? 1;
        $stores = Store::with('channel')
            ->withCount(['marketplaceProducts', 'orders'])
            ->where('tenant_id', $tenantId)
            ->get();

        $connectedCount = $stores->where('status', 'connected')->count();
        $expiredCount = $stores->where('status', 'expired')->count();
        $totalCount = $stores->count();
        $hasExpiredStores = $stores->contains('status', 'expired');

        return view('v2.toko.index', compact('stores', 'connectedCount', 'expiredCount', 'totalCount', 'hasExpiredStores'));
    }

    public function create()
    {
        Channel::ensureChannelsExist();
        $channels = Channel::where('status', true)->get();
        return view('v2.toko.create', compact('channels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_id'           => 'required|exists:channels,id',
            'store_name'           => 'required|string|max:255',
            'marketplace_store_id' => 'required|string|max:100',
            'logo'                 => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:2048',
        ]);

        $tenantId = Auth::user()->tenant_id ?? 1;
        $marketStoreId = trim($data['marketplace_store_id']);

        $updateData = [
            'store_name'       => $data['store_name'],
            'status'           => 'connected',
            'access_token'     => 'manual_access_token_' . time(),
            'refresh_token'    => 'manual_refresh_token_' . time(),
            'token_expires_at' => now()->addYears(5),
        ];

        if ($request->hasFile('logo')) {
            $updateData['logo_path'] = $request->file('logo')->store('store-logos', 'public');
        }

        $store = Store::updateOrCreate(
            [
                'tenant_id'            => $tenantId,
                'channel_id'           => $data['channel_id'],
                'marketplace_store_id' => $marketStoreId,
            ],
            $updateData
        );

        return redirect()->route('v2.toko.index')->with('success', "Toko \"{$store->store_name}\" berhasil ditambahkan ke ERP!");
    }

    public function edit($id)
    {
        $tenantId = Auth::user()->tenant_id ?? 1;
        $store = Store::where('tenant_id', $tenantId)->findOrFail($id);
        Channel::ensureChannelsExist();
        $channels = Channel::where('status', true)->get();

        return view('v2.toko.edit', compact('store', 'channels'));
    }

    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id ?? 1;
        $store = Store::where('tenant_id', $tenantId)->findOrFail($id);

        $data = $request->validate([
            'store_name' => 'required|string|max:255',
            'status'     => 'required|in:connected,disconnected,expired',
            'shipping_handover_method' => 'required|in:DROP_OFF,PICK_UP',
            'logo'       => 'nullable|image|mimes:jpeg,png,jpg,webp,gif,svg|max:2048',
        ]);

        if ($request->boolean('remove_logo')) {
            if ($store->logo_path && Storage::disk('public')->exists($store->logo_path)) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $data['logo_path'] = null;
        } elseif ($request->hasFile('logo')) {
            if ($store->logo_path && Storage::disk('public')->exists($store->logo_path)) {
                Storage::disk('public')->delete($store->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('store-logos', 'public');
        }

        $store->update($data);

        return redirect()->route('v2.toko.index')->with('success', "Pengaturan toko \"{$store->store_name}\" berhasil diperbarui!");
    }

    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id ?? 1;
        $store = Store::where('tenant_id', $tenantId)->findOrFail($id);
        
        $name = $store->store_name;
        $store->delete();

        return redirect()->route('v2.toko.index')->with('success', "Toko \"{$name}\" berhasil dihapus.");
    }
}
