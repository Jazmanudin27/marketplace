<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreController extends Controller
{
    public function index()
    {
        Channel::ensureChannelsExist();
        $stores = Store::with('channel')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->get();

        return view('marketplace.stores.index', compact('stores'));
    }

    public function create()
    {
        Channel::ensureChannelsExist();
        $channels = Channel::where('status', true)->get();
        return view('marketplace.stores.create', compact('channels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channel_id'           => 'required|exists:channels,id',
            'store_name'           => 'required|string|max:255',
            'marketplace_store_id' => 'required|string|max:100',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;
        $data['status']    = 'connected';

        Store::create($data);

        return redirect()->route('stores.index')->with('success', 'Toko berhasil ditambahkan.');
    }

    public function edit(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        Channel::ensureChannelsExist();
        $channels = Channel::where('status', true)->get();
        return view('marketplace.stores.edit', compact('store', 'channels'));
    }

    public function update(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        $data = $request->validate([
            'store_name' => 'required|string|max:255',
            'status'     => 'required|in:connected,disconnected',
            'shipping_handover_method' => 'required|in:DROP_OFF,PICK_UP',
        ]);
        $store->update($data);
        return redirect()->route('stores.index')->with('success', 'Toko berhasil diperbarui.');
    }

    public function destroy(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        $store->delete();
        return redirect()->route('stores.index')->with('success', 'Toko berhasil dihapus.');
    }
}
