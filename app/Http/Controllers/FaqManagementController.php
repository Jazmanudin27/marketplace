<?php

namespace App\Http\Controllers;

use App\Models\FaqCategory;
use App\Models\FaqItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FaqManagementController extends Controller
{
    private function authorizeAdmin(): void
    {
        $user = auth()->user();
        if (!$user || ($user->role !== 'admin' && $user->role !== 'super-admin' && !$user->hasRole('admin'))) {
            abort(403, 'Hanya Administrator yang dapat mengelola Bantuan & FAQ.');
        }
    }

    public function manage()
    {
        $this->authorizeAdmin();
        $categories = FaqCategory::with(['workflows', 'faqs'])->orderBy('sort_order')->get();
        return view('faq.manage', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'subtitle'       => 'nullable|string|max:255',
            'icon'           => 'required|string|max:50',
            'color'          => 'required|string|max:20',
            'color_rgb'      => 'required|string|max:50',
            'read_time'      => 'nullable|string|max:20',
            'workflow_title' => 'required|string|max:100',
            'sort_order'     => 'required|integer',
        ]);

        $data['slug'] = Str::slug($data['name']);
        
        // Ensure slug is unique, append counter if needed
        $originalSlug = $data['slug'];
        $counter = 1;
        while (FaqCategory::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        FaqCategory::create($data);

        return redirect()->route('faq.manage')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, FaqCategory $category)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'name'           => 'required|string|max:100',
            'subtitle'       => 'nullable|string|max:255',
            'icon'           => 'required|string|max:50',
            'color'          => 'required|string|max:20',
            'color_rgb'      => 'required|string|max:50',
            'read_time'      => 'nullable|string|max:20',
            'workflow_title' => 'required|string|max:100',
            'sort_order'     => 'required|integer',
        ]);

        if ($category->name !== $data['name']) {
            $data['slug'] = Str::slug($data['name']);
            $originalSlug = $data['slug'];
            $counter = 1;
            while (FaqCategory::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                $data['slug'] = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $category->update($data);

        return redirect()->route('faq.manage')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyCategory(FaqCategory $category)
    {
        $this->authorizeAdmin();
        $category->delete();
        return redirect()->route('faq.manage')->with('success', 'Kategori berhasil dihapus beserta semua item di dalamnya.');
    }

    public function storeItem(Request $request)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'faq_category_id' => 'required|exists:faq_categories,id',
            'type'            => 'required|in:workflow,faq',
            'title'           => 'required|string',
            'content'         => 'required|string',
            'sort_order'      => 'required|integer',
        ]);

        FaqItem::create($data);

        return redirect()->route('faq.manage')->with('success', 'Item berhasil ditambahkan.');
    }

    public function updateItem(Request $request, FaqItem $item)
    {
        $this->authorizeAdmin();
        $data = $request->validate([
            'faq_category_id' => 'required|exists:faq_categories,id',
            'type'            => 'required|in:workflow,faq',
            'title'           => 'required|string',
            'content'         => 'required|string',
            'sort_order'      => 'required|integer',
        ]);

        $item->update($data);

        return redirect()->route('faq.manage')->with('success', 'Item berhasil diperbarui.');
    }

    public function destroyItem(FaqItem $item)
    {
        $this->authorizeAdmin();
        $item->delete();
        return redirect()->route('faq.manage')->with('success', 'Item berhasil dihapus.');
    }
}
