<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::where('tenant_id', Auth::user()->tenant_id);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $categories = $query->paginate(15)->withQueryString();

        return view('master.categories.index', compact('categories'));
    }

    public function create()
    {
        return redirect()->route('categories.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $data['tenant_id'] = Auth::user()->tenant_id;
        Category::create($data);
        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category)
    {
        return redirect()->route('categories.index');
    }

    public function update(Request $request, Category $category)
    {
        abort_unless($category->tenant_id === Auth::user()->tenant_id, 403);
        $data = $request->validate(['name' => 'required|string|max:255']);
        $category->update($data);
        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diupdate.');
    }

    public function destroy(Category $category)
    {
        abort_unless($category->tenant_id === Auth::user()->tenant_id, 403);
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus.');
    }
}
