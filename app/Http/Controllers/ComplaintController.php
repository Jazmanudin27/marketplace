<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ComplaintController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $complaints = Complaint::where('tenant_id', $tenantId)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('complaints.index', compact('complaints'));
    }

    public function create()
    {
        return view('complaints.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'description' => 'required|string',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'address', 'phone', 'description']);
        $data['tenant_id'] = Auth::user()->tenant_id;
        $data['status'] = 'Pending';

        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
            foreach ($photos as $index => $photo) {
                if ($index < 3) {
                    $path = $photo->store('complaints', 'public');
                    $data['photo_' . ($index + 1)] = $path;
                }
            }
        }

        Complaint::create($data);

        return redirect()->route('complaints.index')
            ->with('success', 'Pengaduan barang rusak berhasil disimpan.');
    }

    public function show(Complaint $complaint)
    {
        abort_unless($complaint->tenant_id === Auth::user()->tenant_id, 403);
        return view('complaints.show', compact('complaint'));
    }

    public function edit(Complaint $complaint)
    {
        abort_unless($complaint->tenant_id === Auth::user()->tenant_id, 403);
        return view('complaints.edit', compact('complaint'));
    }

    public function update(Request $request, Complaint $complaint)
    {
        abort_unless($complaint->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'description' => 'required|string',
            'status' => 'required|in:Pending,Diproses,Selesai,Dibatalkan',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'address', 'phone', 'description', 'status']);

        if ($request->hasFile('photos')) {
            // Hapus file lama
            for ($i = 1; $i <= 3; $i++) {
                $field = 'photo_' . $i;
                if ($complaint->$field) {
                    Storage::disk('public')->delete($complaint->$field);
                    $data[$field] = null;
                }
            }

            $photos = $request->file('photos');
            foreach ($photos as $index => $photo) {
                if ($index < 3) {
                    $path = $photo->store('complaints', 'public');
                    $data['photo_' . ($index + 1)] = $path;
                }
            }
        }

        $complaint->update($data);

        return redirect()->route('complaints.index')
            ->with('success', 'Pengaduan barang rusak berhasil diperbarui.');
    }

    public function destroy(Complaint $complaint)
    {
        abort_unless($complaint->tenant_id === Auth::user()->tenant_id, 403);

        // Hapus file dari storage
        for ($i = 1; $i <= 3; $i++) {
            $field = 'photo_' . $i;
            if ($complaint->$field) {
                Storage::disk('public')->delete($complaint->$field);
            }
        }

        $complaint->delete();

        return redirect()->route('complaints.index')
            ->with('success', 'Pengaduan barang rusak berhasil dihapus.');
    }

    public function mobileCreate($tenant_id)
    {
        $tenant = \App\Models\Tenant::findOrFail($tenant_id);
        return view('complaints.mobile_create', compact('tenant'));
    }

    public function mobileStore(Request $request, $tenant_id)
    {
        $tenant = \App\Models\Tenant::findOrFail($tenant_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'phone' => 'required|string|max:20',
            'description' => 'required|string',
            'photos' => 'nullable|array|max:3',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only(['name', 'address', 'phone', 'description']);
        $data['tenant_id'] = $tenant->id;
        $data['status'] = 'Pending';

        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
            foreach ($photos as $index => $photo) {
                if ($index < 3) {
                    $path = $photo->store('complaints', 'public');
                    $data['photo_' . ($index + 1)] = $path;
                }
            }
        }

        Complaint::create($data);

        return redirect()->route('complaints.mobile.success', $tenant->id);
    }

    public function mobileSuccess($tenant_id)
    {
        $tenant = \App\Models\Tenant::findOrFail($tenant_id);
        return view('complaints.mobile_success', compact('tenant'));
    }
}
