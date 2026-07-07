<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            if (!$user->isSuperAdmin() && $user->role !== 'admin') {
                abort(403, 'Anda tidak memiliki hak akses untuk mengelola Departemen.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = Department::where('tenant_id', Auth::user()->tenant_id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('code')) {
            $query->where('code', 'like', '%' . $request->code . '%');
        }

        $departments = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('master.departments.index', compact('departments'));
    }

    public function create()
    {
        return redirect()->route('departments.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;
        $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;

        Department::create($data);

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil ditambahkan.');
    }

    public function edit(Department $department)
    {
        return redirect()->route('departments.index');
    }

    public function update(Request $request, Department $department)
    {
        abort_unless($department->tenant_id === Auth::user()->tenant_id, 403);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->has('is_active') ? (bool) $request->is_active : true;

        $department->update($data);

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil diupdate.');
    }

    public function destroy(Department $department)
    {
        abort_unless($department->tenant_id === Auth::user()->tenant_id, 403);

        // Check if department has associated POs or stock movements
        $poCount = \App\Models\PurchaseOrder::where('department_id', $department->id)->count();
        $smCount = \App\Models\StockMovement::where('department_id', $department->id)->count();

        if ($poCount > 0 || $smCount > 0) {
            return redirect()->route('departments.index')
                ->withErrors(['delete' => 'Departemen tidak dapat dihapus karena telah digunakan dalam transaksi Pembelian / Barang Masuk.']);
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Departemen berhasil dihapus.');
    }
}
